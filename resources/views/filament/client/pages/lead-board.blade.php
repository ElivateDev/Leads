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
                 style="display: grid;">
                @foreach ($dispositions as $dispositionKey => $dispositionLabel)
                    <div class="filter-option" data-disposition="{{ $dispositionKey }}">
                        <div class="filter-checkbox checked" id="checkbox-{{ $dispositionKey }}">
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
                <span id="visible-columns-info">Showing columns</span>
                <div class="scroll-dots" id="scroll-dots">
                    <!-- Dots will be generated by JavaScript -->
                </div>
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
                @foreach ($this->getColumnOrder() as $dispositionKey)
                    @if(isset($dispositions[$dispositionKey]))
                        @php $dispositionLabel = $dispositions[$dispositionKey]; @endphp
                        <div class="disposition-column" data-disposition="{{ $dispositionKey }}"
                            id="column-{{ $dispositionKey }}" data-column-order="{{ $loop->index }}">
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
                                        <div class="lead-card" draggable="true" data-lead-id="{{ $lead['id'] }}"
                                            data-current-disposition="{{ $dispositionKey }}">
                                            @if (!empty($lead['notes']))
                                                <div class="notes-indicator" title="Has notes"></div>
                                            @endif
                                            <div class="lead-card-actions">
                                                <button class="action-btn"
                                                    onclick="openNotesModal({{ $lead['id'] }}, '{{ addslashes($lead['name']) }}', {{ json_encode($lead['notes'] ?? '') }})"
                                                    title="Notes" x-data="{
                                                        tooltip: @js(!empty($lead['notes']) ? $lead['notes'] : 'Click to add notes'),
                                                        hasNotes: @js(!empty($lead['notes']))
                                                    }" x-tooltip="tooltip">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" />
                                                    </svg>
                                                </button>
                                                <button class="action-btn drag-handle" title="Drag to move">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M5 8a1 1 0 011-1h1a1 1 0 010 2H6a1 1 0 01-1-1zM5 12a1 1 0 011-1h1a1 1 0 110 2H6a1 1 0 01-1-1zM13 8a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1zM13 12a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="lead-name">{{ $lead['name'] }}</div>
                                            @if ($lead['email'])
                                                <div class="lead-info">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                                    </svg>
                                                    <a href="mailto:{{ $lead['email'] }}" class="lead-link"
                                                        onclick="event.stopPropagation();">
                                                        {{ $lead['email'] }}
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($lead['phone'])
                                                <div class="lead-info">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                    </svg>
                                                    <a href="tel:{{ $lead['phone'] }}" class="lead-link"
                                                        onclick="event.stopPropagation();">
                                                        {{ $lead['phone'] }}
                                                    </a>
                                                </div>
                                            @endif
                                            @if ($lead['source'])
                                                <span class="lead-source">{{ $lead['source'] }}</span>
                                            @endif
                                            @if ($lead['campaign'])
                                                <span class="lead-campaign" style="background-color: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-left: 0.25rem;">
                                                    <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $lead['campaign'] }}
                                                </span>
                                            @endif
                                            @if ($lead['created_at'])
                                                <div class="lead-info" style="margin-top: 0.5rem; font-size: 0.75rem;">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                    {{ \Carbon\Carbon::parse($lead['created_at'])->diffForHumans() }}
                                                </div>
                                            @endif
                                        </div>
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
        </div>        <!-- Notes Modal -->
        <div class="notes-modal" id="notes-modal">
            <div class="notes-modal-content">
                <div class="notes-header">
                    <h3 class="notes-title" id="notes-modal-title">Notes for Lead</h3>
                    <button class="close-btn" onclick="closeNotesModal()">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <textarea class="notes-textarea" id="notes-textarea" placeholder="Add your notes here..."></textarea>
                <div class="notes-actions">
                    <button class="btn btn-secondary" onclick="closeNotesModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="saveNotes()">
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
    </div>

    @push('scripts')
        <script>
            // Make Livewire component ID available to external script
            window.livewireComponentId = '{{ $_instance->getId() }}';

            // Pass initial column order from database
            window.initialColumnOrder = @json($this->getColumnOrder());

            // Pass initial visible dispositions
            window.initialVisibleDispositions = @json($visibleDispositions);
        </script>
        <script src="{{ asset('js/lead-board.js') }}"></script>
    @endpush
</x-filament-panels::page>
