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

    public function mount(): void
    {
        $this->loadData();

        // Load user preferences for visible dispositions
        $user = Filament::auth()->user();
        $savedVisibleDispositions = $user->getPreference('leadboard_visible_dispositions');

        if ($savedVisibleDispositions) {
            $this->visibleDispositions = array_intersect($savedVisibleDispositions, array_keys($this->dispositions));
        } else {
            // Initialize visible dispositions to all by default
            $this->visibleDispositions = array_keys($this->dispositions);
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
        ];
    }

    public function updateVisibleDispositions($visibleDispositions): void
    {
        $this->visibleDispositions = array_intersect($visibleDispositions, array_keys($this->dispositions));

        // Save to user preferences
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_visible_dispositions', $this->visibleDispositions);
    }

    public function updateColumnOrder($columnOrder): void
    {
        // Save column order to user preferences
        $user = Filament::auth()->user();
        $user->setPreference('leadboard_column_order', $columnOrder);
    }

    public function getColumnOrder(): array
    {
        $user = Filament::auth()->user();
        return $user->getPreference('leadboard_column_order', array_keys($this->dispositions));
    }
}
