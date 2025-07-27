@props(['filterPanelOpen', 'columnOrder', 'dispositions', 'visibleDispositions', 'leads'])

<div class="filter-panel">
    <div class="filter-header">
        <div class="filter-title" style="cursor: pointer;" wire:click="$toggle('filterPanelOpen')">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                    clip-rule="evenodd" />
            </svg>
            Filter Dispositions
            <svg class="w-4 h-4 filter-toggle-icon" fill="currentColor" viewBox="0 0 20 20"
                style="margin-left: 0.5rem; transition: transform 0.2s ease; transform: {{ $filterPanelOpen ? 'rotate(0deg)' : 'rotate(-90deg)' }};">
                <path fill-rule="evenodd"
                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="quick-actions">
            <button type="button" class="quick-action-btn" wire:click="selectAllDispositions">All</button>
            <button type="button" class="quick-action-btn" wire:click="selectNoneDispositions">None</button>
        </div>
    </div>
    @if($filterPanelOpen)
        <div class="filter-options" id="filter-options">
            @foreach ($columnOrder as $dispositionKey)
                @if(isset($dispositions[$dispositionKey]))
                    @php $dispositionLabel = $dispositions[$dispositionKey]; @endphp
                    <div class="filter-option" wire:click="toggleDisposition('{{ $dispositionKey }}')">
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
    @endif
</div>
