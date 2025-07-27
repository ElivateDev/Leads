<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/lead-board.css') }}">
    @endpush

    <div class="lead-board">
        <!-- Filter Panel -->
        <x-client.components.filter-panel
            :filter-panel-open="$filterPanelOpen"
            :column-order="$columnOrder"
            :dispositions="$dispositions"
            :visible-dispositions="$visibleDispositions"
            :leads="$leads" />

        <!-- Scroll Navigation -->
        <div class="scroll-navigation" id="scroll-navigation">
            <button type="button" class="scroll-btn" id="scroll-left" onclick="scrollColumns('left')">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
                Previous
            </button>

            <div class="scroll-info">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <span id="visible-columns-info">Showing {{ $visibleColumnsCount }} columns</span>
            </div>

            <button type="button" class="scroll-btn" id="scroll-right" onclick="scrollColumns('right')">
                Next
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="columns-wrapper">
            <div class="disposition-columns" id="disposition-columns">
                @foreach ($columnOrder as $dispositionKey)
                    @if(isset($dispositions[$dispositionKey]))
                        @php $dispositionLabel = $dispositions[$dispositionKey]; @endphp
                        <div class="disposition-column" data-disposition="{{ $dispositionKey }}"
                            id="column-{{ $dispositionKey }}" data-column-order="{{ $loop->index }}"
                            style="{{ in_array($dispositionKey, $visibleDispositions) ? '' : 'display: none;' }}">
                            <div class="column-drag-handle" draggable="true">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M5 8a1 1 0 011-1h1a1 1 0 010 2H6a1 1 0 01-1-1zM5 12a1 1 0 011-1h1a1 1 0 110 2H6a1 1 0 01-1-1zM13 8a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1zM13 12a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1z" />
                                </svg>
                            </div>
                            <div class="disposition-header">
                                <h3 class="disposition-title">{{ $dispositionLabel }}</h3>
                                <span class="lead-count">
                                    {{ isset($leads[$dispositionKey]) ? count($leads[$dispositionKey]) : 0 }}
                                </span>
                            </div>

                            <div class="drop-zone" data-disposition="{{ $dispositionKey }}">
                                @if (isset($leads[$dispositionKey]) && count($leads[$dispositionKey]) > 0)
                                    @foreach ($leads[$dispositionKey] as $lead)
                                        <x-client.components.lead-card
                                            :lead="$lead"
                                            :disposition-key="$dispositionKey" />
                                    @endforeach
                                @else
                                    <div class="empty-state">
                                        No leads in {{ strtolower($dispositionLabel) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <x-client.components.notes-modal
            :show-modal="$showNotesModal"
            :current-lead-name="$currentLeadName ?? ''"
            :current-notes="$currentNotes ?? ''" />
    </div>

    @push('scripts')
        <script>
            // Make Livewire component ID available to external script
            window.livewireComponentId = '{{ $_instance->getId() }}';
        </script>
        <script src="{{ asset('js/lead-board.js') }}"></script>
    @endpush
</x-filament-panels::page>
