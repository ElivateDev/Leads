@props(['lead', 'dispositionKey'])

<div class="lead-card" draggable="true" data-lead-id="{{ $lead['id'] }}"
     data-current-disposition="{{ $dispositionKey }}">
    @if (!empty($lead['notes']))
        <div class="notes-indicator" title="Has notes"></div>
    @endif

    <div class="lead-card-actions">
        <x-filament::icon-button
            icon="heroicon-m-document-text"
            wire:click="openNotesModal({{ $lead['id'] }})"
            tooltip="{{ $this->leadTooltips[$lead['id']] ?? 'Click to add notes' }}"
            label="Edit notes for {{ $lead['name'] }}"
            color="{{ !empty($lead['notes']) ? 'success' : 'primary' }}"
            :badge="!empty($lead['notes']) ? 'Notes' : null"
            badgeColor="success"
        />
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
        <span class="lead-campaign" style="background-color: #10b981; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-left: 0.25rem; display: inline-block; max-width: 100%; word-wrap: break-word; overflow-wrap: break-word;">
            @svg('heroicon-c-tag', 'w-3 h-3 mr-1 inline')
            {{ $lead['campaign'] }}
        </span>
    @endif

    @if ($lead['created_at'])
        <div class="lead-info" style="margin-top: 0.5rem; font-size: 0.75rem;">
            @svg('heroicon-c-clock', 'w-4 h-4 mr-1 flex-shrink-0')
            {{ \Carbon\Carbon::parse($lead['created_at'])->diffForHumans() }}
        </div>
    @endif
</div>
