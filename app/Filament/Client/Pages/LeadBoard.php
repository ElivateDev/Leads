<?php

namespace App\Filament\Client\Pages;

use App\Models\Lead;
use App\Models\UserPreference;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Livewire\Attributes\On;
use Filament\Notifications\Notification;

class LeadBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.client.pages.lead-board';

    protected static ?string $navigationLabel = 'Lead Board';

    protected static ?string $title = 'Lead Board';

    protected static ?int $navigationSort = 2;

    public $leads = [];
    public $dispositions = [];
    public $visibleDispositions = [];
    public $filterPanelOpen = true;
    public $columnOrder = [];
    public $showNotesModal = false;
    public $currentLeadId = null;
    public $currentLeadName = '';
    public $currentNotes = '';

    public function mount(): void
    {
        $this->loadData();

        // Load user preferences for visible dispositions
        $user = Filament::auth()->user();
        $savedVisibleDispositions = $user->getPreference('leadboard_visible_dispositions');
        $savedFilterPanelOpen = $user->getPreference('leadboard_filter_panel_open');
        $savedColumnOrder = $user->getPreference('leadboard_column_order');

        if ($savedVisibleDispositions) {
            $this->visibleDispositions = array_intersect($savedVisibleDispositions, array_keys($this->dispositions));
        } else {
            // Initialize visible dispositions to all by default
            $this->visibleDispositions = array_keys($this->dispositions);
        }

        // Set filter panel state
        $this->filterPanelOpen = $savedFilterPanelOpen !== null ? $savedFilterPanelOpen : true;

        // Set column order
        if ($savedColumnOrder) {
            $this->columnOrder = array_intersect($savedColumnOrder, array_keys($this->dispositions));
        } else {
            $this->columnOrder = array_keys($this->dispositions);
        }
    }

    protected function loadData(): void
    {
        $user = Filament::auth()->user();
        $client = $user->client;

        $this->dispositions = $client->getLeadDispositions();

        // Group leads by disposition
        $leads = Lead::where('client_id', $user->client_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->leads = $leads->groupBy('status')->toArray();
    }

    public function updateLeadDisposition($leadId, $newDisposition): void
    {
        $user = Filament::auth()->user();

        $lead = Lead::where('id', $leadId)
            ->where('client_id', $user->client_id)
            ->first();

        if ($lead && array_key_exists($newDisposition, $this->dispositions)) {
            $oldDisposition = $lead->status;
            $lead->update(['status' => $newDisposition]);

            // Refresh the leads data
            $this->loadData();

            // Dispatch event to reinitialize drag and drop
            $this->dispatch('leadUpdated');

            Notification::make()
                ->title('Lead Updated')
                ->body("'{$lead->name}' moved from {$this->dispositions[$oldDisposition]} to {$this->dispositions[$newDisposition]}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error')
                ->body('Unable to update lead disposition')
                ->danger()
                ->send();
        }
    }

    public function openNotesModal($leadId): void
    {
        $user = Filament::auth()->user();

        $lead = Lead::where('id', $leadId)
            ->where('client_id', $user->client_id)
            ->first();

        if ($lead) {
            $this->currentLeadId = $leadId;
            $this->currentLeadName = $lead->name;
            $this->currentNotes = $lead->notes ?? '';
            $this->showNotesModal = true;
        }
    }

    public function closeNotesModal(): void
    {
        $this->showNotesModal = false;
        $this->currentLeadId = null;
        $this->currentLeadName = '';
        $this->currentNotes = '';
    }

    public function saveNotes(): void
    {
        if (!$this->currentLeadId)
            return;

        $user = Filament::auth()->user();

        $lead = Lead::where('id', $this->currentLeadId)
            ->where('client_id', $user->client_id)
            ->first();

        if ($lead) {
            $lead->update(['notes' => $this->currentNotes]);

            // Refresh the leads data to show updated notes indicator
            $this->loadData();

            Notification::make()
                ->title('Notes Updated')
                ->body("Notes updated for '{$lead->name}'")
                ->success()
                ->send();

            $this->closeNotesModal();
        } else {
            Notification::make()
                ->title('Error')
                ->body('Unable to update lead notes')
                ->danger()
                ->send();
        }
    }

    public function updateLeadNotes($leadId, $notes): void
    {
        $user = Filament::auth()->user();

        $lead = Lead::where('id', $leadId)
            ->where('client_id', $user->client_id)
            ->first();

        if ($lead) {
            $lead->update(['notes' => $notes]);

            // Refresh the leads data
            $this->loadData();

            Notification::make()
                ->title('Notes Updated')
                ->body("Notes updated for '{$lead->name}'")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error')
                ->body('Unable to update lead notes')
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        return [
            'dispositions' => $this->dispositions,
            'leads' => $this->leads,
            'visibleDispositions' => $this->visibleDispositions,
            'filterPanelOpen' => $this->filterPanelOpen,
            'columnOrder' => $this->columnOrder,
            'visibleColumnsCount' => $this->getVisibleColumnsCountProperty(),
            'showNotesModal' => $this->showNotesModal,
            'currentLeadName' => $this->currentLeadName,
        ];
    }

    public function getVisibleColumnsCountProperty(): int
    {
        return count($this->visibleDispositions);
    }

    public function getNotesTooltip($leadNotes): string
    {
        if (!empty($leadNotes)) {
            // Truncate long notes for tooltip display
            return strlen($leadNotes) > 100
                ? substr($leadNotes, 0, 100) . '...'
                : $leadNotes;
        }

        return 'Click to add notes';
    }

    public function updatedFilterPanelOpen(): void
    {
        // Automatically save filter panel state when it changes
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_filter_panel_open', $this->filterPanelOpen);
    }

    public function updatedVisibleDispositions(): void
    {
        // Automatically save visible dispositions when they change
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_visible_dispositions', $this->visibleDispositions);
    }

    public function updatedColumnOrder(): void
    {
        // Automatically save column order when it changes
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_column_order', $this->columnOrder);
    }

    public function toggleDisposition(string $dispositionKey): void
    {
        if (in_array($dispositionKey, $this->visibleDispositions)) {
            // Remove from visible dispositions
            $this->visibleDispositions = array_filter(
                $this->visibleDispositions,
                fn($key) => $key !== $dispositionKey
            );
        } else {
            // Add to visible dispositions
            $this->visibleDispositions[] = $dispositionKey;
        }

        // Re-index array to ensure clean indexes
        $this->visibleDispositions = array_values($this->visibleDispositions);
    }

    public function selectAllDispositions(): void
    {
        $this->visibleDispositions = array_keys($this->dispositions);
    }

    public function selectNoneDispositions(): void
    {
        $this->visibleDispositions = [];
    }
}
