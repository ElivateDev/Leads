<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/lead-board.css') }}">
    @endpush

    <div class="lead-board">
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="filter-header">
                <div class="filter-title" style="cursor: pointer;">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                            clip-rule="evenodd" />
                    </svg>
                    Filter Dispositions
                    <svg class="w-4 h-4 filter-toggle-icon" fill="currentColor" viewBox="0 0 20 20"
                        style="margin-left: 0.5rem; transition: transform 0.2s ease; transform: rotate(0deg);">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="quick-actions">
                    <button type="button" class="quick-action-btn">All</button>
                    <button type="button" class="quick-action-btn">None</button>
                </div>
            </div>
            <div class="filter-options" id="filter-options"
                 style="display: {{ $filterPanelOpen ? 'grid' : 'none' }};">
                @foreach ($columnOrder as $dispositionKey)
                    @if(isset($dispositions[$dispositionKey]))
                        @php $dispositionLabel = $dispositions[$dispositionKey]; @endphp
                        <div class="filter-option" data-disposition="{{ $dispositionKey }}">
                            <div class="filter-checkbox {{ in_array($dispositionKey, $visibleDispositions) ? 'checked' : '' }}"
                                 id="checkbox-{{ $dispositionKey }}">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="filter-label">{{ $dispositionLabel }}</span>
                            <span class="lead-count" style="margin-left: auto;">
                                {{ isset($leads[$dispositionKey]) ? count($leads[$dispositionKey]) : 0 }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

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

        <!-- Notes Modal -->
        <x-client.components.notes-modal
            :show-modal="$showNotesModal"
            :current-lead-name="$currentLeadName ?? ''"
            :current-notes="$currentNotes ?? ''" />
    </div>

    @push('scripts')
        <script>
            // Make Livewire component ID available to external script
            window.livewireComponentId = '{{ $_instance->getId() }}';

            // Pass initial column order from database
            window.initialColumnOrder = @json($columnOrder);

            // Pass initial visible dispositions
            window.initialVisibleDispositions = @json($visibleDispositions);

            // Pass initial filter panel state
            window.initialFilterPanelOpen = @json($filterPanelOpen);
        </script>
        <script src="{{ asset('js/lead-board.js') }}"></script>
    @endpush
</x-filament-panels::page>
