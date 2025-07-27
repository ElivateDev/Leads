<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/lead-board.css') }}">
    @endpush

    <div class="lead-board">

        <x-client.components.filter-panel
            :filter-panel-open="$filterPanelOpen"
            :column-order="$columnOrder"
            :dispositions="$dispositions"
            :visible-dispositions="$visibleDispositions"
            :leads="$leads" />

        <x-client.components.scroll-navigation
            :visible-columns-count="$visibleColumnsCount" />

        <div class="columns-wrapper">
            <div class="disposition-columns" id="disposition-columns">
                @foreach ($columnOrder as $dispositionKey)
                    @if(isset($dispositions[$dispositionKey]))
                        @php $dispositionLabel = $dispositions[$dispositionKey]; @endphp
                        <x-client.components.disposition-column
                            :disposition-key="$dispositionKey"
                            :disposition-label="$dispositionLabel"
                            :leads="$leads"
                            :visible-dispositions="$visibleDispositions"
                            :loop-index="$loop->index"
                            wire:key="column-{{ $dispositionKey }}" />
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
