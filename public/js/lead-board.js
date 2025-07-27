// Lead Board JavaScript Functionality

let draggedElement = null;
let draggedLeadId = null;
let draggedFromDisposition = null;
let visibleDispositions = [];
let columnOrder = [];
let draggedColumn = null;
let dropIndicator = null;
let filterPanelOpen = false;

function getLivewireComponent() {
    const wireElement = document.querySelector('[wire\\:id]');
    if (wireElement) {
        return Livewire.find(wireElement.getAttribute('wire:id'));
    }
    return null;
}

function initializeDragAndDrop() {
    document.querySelectorAll('.lead-card').forEach(card => {
        card.draggable = true;
    });
}

function initializeFilters() {
    filterPanelOpen = window.initialFilterPanelOpen !== undefined ? window.initialFilterPanelOpen : true;

    const filterOptions = document.getElementById('filter-options');
    const toggleIcon = document.querySelector('.filter-toggle-icon');

    if (!filterPanelOpen) {
        filterOptions.style.display = 'none';
        if (toggleIcon) toggleIcon.style.transform = 'rotate(-90deg)';
    }

    if (window.initialVisibleDispositions) {
        visibleDispositions = window.initialVisibleDispositions;
    } else {
        visibleDispositions = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
            col.dataset.disposition
        );
    }

    initializeColumnOrderOnLoad();
    initializeFilterEventListeners();
    updateFilterDisplay();
    updateColumnVisibilityOnly();
    updateScrollIndicator();
}

function initializeColumnOrderOnLoad() {
    if (window.initialColumnOrder) {
        columnOrder = window.initialColumnOrder;
        applyColumnOrder();
    } else {
        columnOrder = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
            col.dataset.disposition
        );
    }
}

function applyColumnOrder() {
    const columnsContainer = document.getElementById('disposition-columns');
    if (!columnsContainer) return;

    const columns = Array.from(columnsContainer.children);
    const currentOrder = columns.map(col => col.dataset.disposition);
    const needsReordering = !currentOrder.every((disposition, index) => disposition === columnOrder[index]);

    if (!needsReordering) {
        return;
    }

    columnsContainer.style.transition = 'opacity 0.15s ease';
    columnsContainer.style.opacity = '0.7';

    columnOrder.forEach((dispositionKey, index) => {
        const column = columns.find(col => col.dataset.disposition === dispositionKey);
        if (column) {
            columnsContainer.appendChild(column);
        }
    });

    setTimeout(() => {
        columnsContainer.style.opacity = '1';
        setTimeout(() => {
            columnsContainer.style.transition = '';
        }, 150);
    }, 50);
}

function saveColumnOrder() {
    const columns = Array.from(document.querySelectorAll('.disposition-column'));
    columnOrder = columns.map(col => col.dataset.disposition);

    // Directly set the Livewire property instead of calling a method
    getLivewireComponent()?.set('columnOrder', columnOrder);
}

function showDropIndicator(targetColumn, isAfter) {
    hideDropIndicator();

    dropIndicator = document.createElement('div');
    dropIndicator.className = 'column-drop-indicator show';

    if (isAfter) {
        dropIndicator.style.right = '-2px';
    } else {
        dropIndicator.style.left = '-2px';
    }

    targetColumn.appendChild(dropIndicator);
}

function hideDropIndicator() {
    if (dropIndicator) {
        dropIndicator.remove();
        dropIndicator = null;
    }
}

function updateScrollIndicator() {
    const columnsContainer = document.getElementById('disposition-columns');
    if (columnsContainer) {
        // Update scroll navigation visibility
        updateScrollNavigation();
    }
}

function updateScrollNavigation() {
    const columnsContainer = document.getElementById('disposition-columns');
    const scrollNavigation = document.getElementById('scroll-navigation');
    const scrollLeftBtn = document.getElementById('scroll-left');
    const scrollRightBtn = document.getElementById('scroll-right');

    if (!columnsContainer || !scrollNavigation) return;

    const hasScroll = columnsContainer.scrollWidth > columnsContainer.clientWidth;

    // Show/hide scroll navigation
    if (hasScroll) {
        scrollNavigation.style.display = 'flex';

        // Update button states
        const scrollLeft = columnsContainer.scrollLeft;
        const maxScrollLeft = columnsContainer.scrollWidth - columnsContainer.clientWidth;

        scrollLeftBtn.disabled = scrollLeft <= 0;
        scrollRightBtn.disabled = scrollLeft >= maxScrollLeft;
    } else {
        scrollNavigation.style.display = 'none';
    }
}

function scrollColumns(direction) {
    const columnsContainer = document.getElementById('disposition-columns');
    if (!columnsContainer) return;

    const scrollAmount = columnsContainer.clientWidth * 0.8; // Scroll 80% of container width

    if (direction === 'left') {
        columnsContainer.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else {
        columnsContainer.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
}

function scrollToPage(pageIndex) {
    const columnsContainer = document.getElementById('disposition-columns');
    if (!columnsContainer) return;

    const containerWidth = columnsContainer.clientWidth;
    const scrollLeft = pageIndex * containerWidth;

    columnsContainer.scrollTo({
        left: scrollLeft,
        behavior: 'smooth'
    });
}

function updateFilterDisplay() {
    document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
        const dispositionKey = checkbox.id.replace('checkbox-', '');
        const isVisible = visibleDispositions.includes(dispositionKey);

        if (isVisible) {
            checkbox.classList.add('checked');
        } else {
            checkbox.classList.remove('checked');
        }
    });
}

function updateColumnVisibilityOnly() {
    // Show/hide columns based on filter
    document.querySelectorAll('.disposition-column').forEach(column => {
        const dispositionKey = column.dataset.disposition;
        const isVisible = visibleDispositions.includes(dispositionKey);

        if (isVisible) {
            column.style.display = '';
        } else {
            column.style.display = 'none';
        }
    });

    // Update scroll indicator after visibility changes
    setTimeout(() => {
        updateScrollIndicator();
    }, 100);
}

function toggleDisposition(dispositionKey) {
    const index = visibleDispositions.indexOf(dispositionKey);

    if (index > -1) {
        visibleDispositions.splice(index, 1);
    } else {
        visibleDispositions.push(dispositionKey);
    }

    updateFilterDisplay();
    updateColumnVisibilityOnly();

    if (toggleDisposition.timeout) {
        clearTimeout(toggleDisposition.timeout);
    }
    toggleDisposition.timeout = setTimeout(() => {
        // Directly set the Livewire property instead of calling a method
        getLivewireComponent()?.set('visibleDispositions', visibleDispositions);
    }, 500);
}

function selectAllDispositions() {
    visibleDispositions = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
        col.dataset.disposition
    );

    updateFilterDisplay();
    updateColumnVisibilityOnly();

    if (toggleDisposition.timeout) {
        clearTimeout(toggleDisposition.timeout);
    }
    // Directly set the Livewire property instead of calling a method
    getLivewireComponent()?.set('visibleDispositions', visibleDispositions);
}

function selectNoneDispositions() {
    visibleDispositions = [];

    updateFilterDisplay();
    updateColumnVisibilityOnly();
    // Directly set the Livewire property instead of calling a method
    getLivewireComponent()?.set('visibleDispositions', visibleDispositions);
}

function toggleFilterPanel() {
    const filterOptions = document.getElementById('filter-options');
    const toggleIcon = document.querySelector('.filter-toggle-icon');

    filterPanelOpen = !filterPanelOpen;

    if (filterPanelOpen) {
        filterOptions.style.display = 'grid';
        if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
    } else {
        filterOptions.style.display = 'none';
        if (toggleIcon) toggleIcon.style.transform = 'rotate(-90deg)';
    }

    // Directly set the Livewire property instead of calling a method
    getLivewireComponent()?.set('filterPanelOpen', filterPanelOpen);
}

function initializeFilterEventListeners() {
    const filterPanel = document.querySelector('.filter-panel');
    if (!filterPanel) return;

    filterPanel.removeEventListener('click', handleFilterPanelClick);
    filterPanel.addEventListener('click', handleFilterPanelClick);
}

// Handle all filter panel clicks in one place
function handleFilterPanelClick(e) {
    const target = e.target;

    // Check if click is on a filter option or its children
    const filterOption = target.closest('.filter-option');
    if (filterOption) {
        e.preventDefault();
        e.stopPropagation();

        const dispositionKey = filterOption.dataset.disposition;
        toggleDisposition(dispositionKey);

        return;
    }

    // Check if click is on filter title or its children (but not in filter options area)
    const filterTitle = target.closest('.filter-title');
    if (filterTitle) {
        e.preventDefault();
        e.stopPropagation();

        toggleFilterPanel();
        return;
    }

    // Check if click is on quick action buttons
    if (target.classList.contains('quick-action-btn')) {
        e.preventDefault();
        e.stopPropagation();

        const buttonText = target.textContent.trim();
        if (buttonText === 'All') {
            selectAllDispositions();
        } else if (buttonText === 'None') {
            selectNoneDispositions();
        }
        return;
    }
}

// Event Listeners

document.addEventListener('dragstart', function(e) {
    // Check if we're dragging a column (from the drag handle)
    if (e.target.closest('.column-drag-handle')) {
        const column = e.target.closest('.disposition-column');
        draggedColumn = column;
        column.classList.add('column-dragging');

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'column-' + column.dataset.disposition);
        return;
    }

    // Handle lead card drag
    if (e.target.closest('.lead-card')) {
        const card = e.target.closest('.lead-card');
        draggedElement = card;
        draggedLeadId = card.dataset.leadId;
        draggedFromDisposition = card.dataset.currentDisposition;
        card.classList.add('dragging');

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedLeadId);
    }
});

document.addEventListener('dragend', function(e) {
    // Handle column drag end
    if (draggedColumn) {
        draggedColumn.classList.remove('column-dragging');
        hideDropIndicator();
        draggedColumn = null;
        return;
    }

    // Handle lead card drag end
    if (e.target.closest('.lead-card')) {
        const card = e.target.closest('.lead-card');
        card.classList.remove('dragging');

        // Clean up drag over states
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.classList.remove('drag-over');
        });
    }
});

document.addEventListener('dragover', function(e) {
    e.preventDefault();

    // Handle column reordering
    if (draggedColumn) {
        const column = e.target.closest('.disposition-column');
        if (column && column !== draggedColumn) {
            const rect = column.getBoundingClientRect();
            const midpoint = rect.left + rect.width / 2;
            const isAfter = e.clientX > midpoint;

            showDropIndicator(column, isAfter);
        }
        return;
    }

    // Handle lead drop zones
    const dropZone = e.target.closest('.drop-zone');
    if (dropZone) {
        e.dataTransfer.dropEffect = 'move';
        dropZone.classList.add('drag-over');
    }
});

document.addEventListener('dragleave', function(e) {
    // Handle column reordering
    if (draggedColumn) {
        const column = e.target.closest('.disposition-column');
        if (!column || !column.contains(e.relatedTarget)) {
            hideDropIndicator();
        }
        return;
    }

    // Handle lead drop zones
    const dropZone = e.target.closest('.drop-zone');
    if (dropZone && !dropZone.contains(e.relatedTarget)) {
        dropZone.classList.remove('drag-over');
    }
});

document.addEventListener('drop', function(e) {
    e.preventDefault();

    // Handle column reordering
    if (draggedColumn) {
        const targetColumn = e.target.closest('.disposition-column');

        if (targetColumn && targetColumn !== draggedColumn) {
            const rect = targetColumn.getBoundingClientRect();
            const midpoint = rect.left + rect.width / 2;
            const isAfter = e.clientX > midpoint;

            const container = document.getElementById('disposition-columns');
            if (isAfter) {
                container.insertBefore(draggedColumn, targetColumn.nextSibling);
            } else {
                container.insertBefore(draggedColumn, targetColumn);
            }

            saveColumnOrder();
        }
        hideDropIndicator();
        return;
    }

    // Handle lead drops
    const dropZone = e.target.closest('.drop-zone');
    if (dropZone) {
        dropZone.classList.remove('drag-over');

        const newDisposition = dropZone.dataset.disposition;

        // Don't do anything if dropped in same disposition
        if (newDisposition === draggedFromDisposition) {
            return;
        }

        // Update lead disposition via Livewire
        if (draggedLeadId && newDisposition) {
            getLivewireComponent()?.call('updateLeadDisposition', draggedLeadId, newDisposition);
        }
    }
});

document.addEventListener('livewire:updated', function(event) {
    setTimeout(() => {
        initializeDragAndDrop();
        updateScrollIndicator();
        updateFilterDisplay();
        updateColumnVisibilityOnly();
    }, 100);
});

window.addEventListener('resize', function() {
    setTimeout(() => {
        updateScrollIndicator();
    }, 100);
});

document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();
    initializeFilters();

    const columnsContainer = document.getElementById('disposition-columns');
    if (columnsContainer) {
        columnsContainer.addEventListener('scroll', function() {
            updateScrollNavigation();
        });
    }
});
