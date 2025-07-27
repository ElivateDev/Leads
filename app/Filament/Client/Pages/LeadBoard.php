<?php

namespace App\Filament\Client\Pages;

use App\Models\Lead;
use App\Models\UserPreference;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Filament\Notifications\Notification;

class LeadBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string $view = 'filament.client.pages.lead-board';

    protected static ?string $navigationLabel = 'Lead Board';

    protected static ?string $title = 'Lead Board';

    protected static ?int $navigationSort = 2;

    // Livewire properties
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

        $leads = Lead::where('client_id', $user->client_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->leads = $leads->groupBy('status')->toArray();

        // Dispatch event to update tooltips
        $this->dispatch('tooltipsUpdated');
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

            $this->loadData();

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

    protected function getViewData(): array
    {
        return [
            'dispositions' => $this->dispositions,
            'leads' => $this->leads,
            'visibleDispositions' => $this->visibleDispositions,
            'filterPanelOpen' => $this->filterPanelOpen,
            'columnOrder' => $this->columnOrder,
            'visibleColumnsCount' => $this->visibleColumnsCount,
            'showNotesModal' => $this->showNotesModal,
            'currentLeadName' => $this->currentLeadName,
        ];
    }

    #[Computed]
    public function visibleColumnsCount(): int
    {
        return count($this->visibleDispositions);
    }

    #[Computed]
    public function leadTooltips()
    {
        $tooltips = [];

        foreach ($this->leads as $dispositionKey => $leadsInDisposition) {
            foreach ($leadsInDisposition as $lead) {
                $tooltips[$lead['id']] = $this->generateTooltipText($lead['notes'] ?? '');
            }
        }

        return $tooltips;
    }

    private function generateTooltipText(string $notes): string
    {
        if (!empty($notes)) {
            // Truncate long notes for tooltip display
            return strlen($notes) > 100
                ? substr($notes, 0, 100) . '...'
                : $notes;
        }

        return 'Click to add notes';
    }

    public function updatedFilterPanelOpen(): void
    {
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_filter_panel_open', $this->filterPanelOpen);
    }

    public function updatedVisibleDispositions(): void
    {
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

        // Explicitly save preferences since updated hook might not trigger
        $this->saveVisibleDispositionsPreference();
    }

    public function selectAllDispositions(): void
    {
        $this->visibleDispositions = array_keys($this->dispositions);

        $this->saveVisibleDispositionsPreference();
    }

    public function selectNoneDispositions(): void
    {
        $this->visibleDispositions = [];

        // Explicitly save preferences
        $this->saveVisibleDispositionsPreference();
    }

    private function saveVisibleDispositionsPreference(): void
    {
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_visible_dispositions', $this->visibleDispositions);

        // Debug logging
        \Log::info('Visible dispositions saved explicitly', [
            'user_id' => $user->id,
            'visible_dispositions' => $this->visibleDispositions
        ]);
    }
}
