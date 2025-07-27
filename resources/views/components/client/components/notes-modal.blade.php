@props(['showModal', 'currentLeadName', 'currentNotes'])

@if($showModal)
    <div class="notes-modal show" wire:click.self="closeNotesModal">
        <div class="notes-modal-content">
            <div class="notes-header">
                <h3 class="notes-title">Notes for {{ $currentLeadName }}</h3>
                <button class="close-btn" wire:click="closeNotesModal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <textarea
                class="notes-textarea"
                wire:model="currentNotes"
                placeholder="Add your notes here..."
                wire:keydown.escape="closeNotesModal">
            </textarea>
            <div class="notes-actions">
                <button class="btn btn-secondary" wire:click="closeNotesModal">Cancel</button>
                <button class="btn btn-primary" wire:click="saveNotes">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    Save Notes
                </button>
            </div>
        </div>
    </div>
@endif
