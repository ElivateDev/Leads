@props(['dispositionKey', 'dispositionLabel', 'leads', 'visibleDispositions', 'loopIndex'])

<div class="disposition-column" data-disposition="{{ $dispositionKey }}"
    id="column-{{ $dispositionKey }}" data-column-order="{{ $loopIndex }}"
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
