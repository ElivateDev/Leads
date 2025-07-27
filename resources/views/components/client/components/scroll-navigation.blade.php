@props(['visibleColumnsCount', 'shouldShowScrollNavigation' => true])

<div class="scroll-navigation" id="scroll-navigation" style="{{ $shouldShowScrollNavigation ? '' : 'display: none;' }}">
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
