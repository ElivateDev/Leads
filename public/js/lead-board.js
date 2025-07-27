let draggedElement = null;
let draggedLeadId = null;
let draggedFromDisposition = null;
let draggedColumn = null;
let dropIndicator = null;

function getLivewireComponent() {
    if (window.livewireComponentId) {
        return Livewire.find(window.livewireComponentId);
    }

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

    document.querySelectorAll('.column-drag-handle').forEach(handle => {
        const column = handle.closest('.disposition-column');
        if (column) {
            handle.draggable = true;
        }
    });
}

function saveColumnOrder() {
    const columns = Array.from(document.querySelectorAll('.disposition-column'));
    const columnOrder = columns.map(col => col.dataset.disposition);

    const component = getLivewireComponent();
    if (component) {
        component.call('updateColumnOrder', columnOrder);
    }
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
        updateScrollNavigation();
    }, 100);
});

window.addEventListener('resize', function() {
    setTimeout(() => {
        updateScrollNavigation();
    }, 100);
});

document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();

    const columnsContainer = document.getElementById('disposition-columns');
    if (columnsContainer) {
        columnsContainer.addEventListener('scroll', function() {
            updateScrollNavigation();
        });
    }
});
