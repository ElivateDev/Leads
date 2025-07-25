// Lead Board JavaScript Functionality

let draggedElement = null;
let draggedLeadId = null;
let draggedFromDisposition = null;
let visibleDispositions = [];
let columnOrder = [];
let draggedColumn = null;
let dropIndicator = null;
let currentLeadId = null;

// Notes Modal Functions
function openNotesModal(leadId, leadName, currentNotes) {
    currentLeadId = leadId;
    document.getElementById('notes-modal-title').textContent = `Notes for ${leadName}`;
    document.getElementById('notes-textarea').value = currentNotes || '';
    document.getElementById('notes-modal').classList.add('show');

    // Focus on textarea after modal animation
    setTimeout(() => {
        document.getElementById('notes-textarea').focus();
    }, 200);
}

function closeNotesModal() {
    document.getElementById('notes-modal').classList.remove('show');
    currentLeadId = null;
}

function saveNotes() {
    if (!currentLeadId) return;

    const notes = document.getElementById('notes-textarea').value;

    // Call Livewire method to save notes
    window.Livewire.find(window.livewireComponentId).call('updateLeadNotes', currentLeadId, notes);

    closeNotesModal();
}

// Initialize drag and drop functionality
function initializeDragAndDrop() {
    console.log('Initializing drag and drop...');

    // Make sure all lead cards are draggable
    document.querySelectorAll('.lead-card').forEach(card => {
        card.draggable = true;
    });
}

// Initialize filter functionality
function initializeFilters() {
    // Load visible dispositions from localStorage or set all as default
    const saved = localStorage.getItem('leadboard-visible-dispositions');
    if (saved) {
        visibleDispositions = JSON.parse(saved);
    } else {
        // Default to all dispositions visible
        visibleDispositions = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
            col.dataset.disposition
        );
    }

    // Load column order from localStorage
    initializeColumnOrder();

    updateFilterDisplay();
    updateColumnVisibility();
    updateScrollIndicator();
}

// Initialize column ordering
function initializeColumnOrder() {
    const savedOrder = localStorage.getItem('leadboard-column-order');
    if (savedOrder) {
        columnOrder = JSON.parse(savedOrder);
        console.log('Loaded saved column order:', columnOrder);
        // Apply the saved order
        applyColumnOrder();
    } else {
        // Default order based on current DOM order
        columnOrder = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
            col.dataset.disposition
        );
        console.log('Using default column order:', columnOrder);
    }
}

// Apply column order to DOM
function applyColumnOrder() {
    const columnsContainer = document.getElementById('disposition-columns');
    if (!columnsContainer) return;

    // Add a brief opacity transition to smooth the reordering
    columnsContainer.style.transition = 'opacity 0.15s ease';
    columnsContainer.style.opacity = '0.7';

    const columns = Array.from(columnsContainer.children);
    console.log('Applying column order:', columnOrder);
    console.log('Current columns:', columns.map(col => col.dataset.disposition));

    // Sort columns based on saved order
    columnOrder.forEach((dispositionKey, index) => {
        const column = columns.find(col => col.dataset.disposition === dispositionKey);
        if (column) {
            columnsContainer.appendChild(column);
        }
    });

    // Restore opacity after a brief delay
    setTimeout(() => {
        columnsContainer.style.opacity = '1';
        setTimeout(() => {
            columnsContainer.style.transition = '';
        }, 150);
    }, 50);

    console.log('Column order applied');
}

// Save column order to localStorage
function saveColumnOrder() {
    const columns = Array.from(document.querySelectorAll('.disposition-column'));
    columnOrder = columns.map(col => col.dataset.disposition);
    localStorage.setItem('leadboard-column-order', JSON.stringify(columnOrder));
}

// Show drop indicator for column reordering
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

// Hide drop indicator
function hideDropIndicator() {
    if (dropIndicator) {
        dropIndicator.remove();
        dropIndicator = null;
    }
}

// Update scroll indicator
function updateScrollIndicator() {
    const columnsContainer = document.getElementById('disposition-columns');
    if (columnsContainer) {
        const hasScroll = columnsContainer.scrollWidth > columnsContainer.clientWidth;

        if (hasScroll) {
            columnsContainer.classList.add('has-scroll');
        } else {
            columnsContainer.classList.remove('has-scroll');
        }

        // Update scroll navigation visibility
        updateScrollNavigation();
    }
}

// Update scroll navigation
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

        // Update scroll dots and info
        updateScrollDots();
        updateVisibleColumnsInfo();
    } else {
        scrollNavigation.style.display = 'none';
    }
}

// Update scroll dots
function updateScrollDots() {
    const columnsContainer = document.getElementById('disposition-columns');
    const scrollDotsContainer = document.getElementById('scroll-dots');
    const visibleColumns = Array.from(document.querySelectorAll('.disposition-column')).filter(col =>
        col.style.display !== 'none'
    );

    if (!columnsContainer || !scrollDotsContainer || visibleColumns.length <= 3) {
        scrollDotsContainer.innerHTML = '';
        return;
    }

    // Calculate number of "pages" (groups of ~3 columns visible at once)
    const containerWidth = columnsContainer.clientWidth;
    const columnWidth = 320 + 24; // min-width + gap
    const columnsPerPage = Math.floor(containerWidth / columnWidth) || 1;
    const totalPages = Math.ceil(visibleColumns.length / columnsPerPage);

    // Clear existing dots
    scrollDotsContainer.innerHTML = '';

    // Create dots
    for (let i = 0; i < totalPages; i++) {
        const dot = document.createElement('div');
        dot.className = 'scroll-dot';
        dot.onclick = () => scrollToPage(i);
        scrollDotsContainer.appendChild(dot);
    }

    // Update active dot
    const scrollLeft = columnsContainer.scrollLeft;
    const currentPage = Math.floor(scrollLeft / (containerWidth));
    const dots = scrollDotsContainer.querySelectorAll('.scroll-dot');
    dots.forEach((dot, index) => {
        if (index === currentPage) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

// Update visible columns info
function updateVisibleColumnsInfo() {
    const infoElement = document.getElementById('visible-columns-info');
    const visibleColumns = Array.from(document.querySelectorAll('.disposition-column')).filter(col =>
        col.style.display !== 'none'
    );

    if (infoElement) {
        infoElement.textContent = `Showing ${visibleColumns.length} columns`;
    }
}

// Scroll columns left or right
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

// Scroll to specific page
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

// Update filter display
function updateFilterDisplay() {
    // Update checkbox states
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

// Update column visibility based on filters
function updateColumnVisibility() {
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

    // Save to localStorage
    localStorage.setItem('leadboard-visible-dispositions', JSON.stringify(visibleDispositions));

    // Update scroll indicator after visibility changes
    setTimeout(() => {
        updateScrollIndicator();
    }, 100);
}

// Toggle disposition visibility
function toggleDisposition(dispositionKey) {
    const index = visibleDispositions.indexOf(dispositionKey);

    if (index > -1) {
        // Remove from visible
        visibleDispositions.splice(index, 1);
    } else {
        // Add to visible
        visibleDispositions.push(dispositionKey);
    }

    updateFilterDisplay();
    updateColumnVisibility();
}

// Select all dispositions
function selectAllDispositions() {
    visibleDispositions = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
        col.dataset.disposition
    );
    updateFilterDisplay();
    updateColumnVisibility();
}

// Select no dispositions
function selectNoneDispositions() {
    visibleDispositions = [];
    updateFilterDisplay();
    updateColumnVisibility();
}

// Toggle filter panel
function toggleFilterPanel() {
    const filterOptions = document.getElementById('filter-options');
    const toggleIcon = document.querySelector('.filter-toggle-icon');

    if (filterOptions.classList.contains('hidden')) {
        filterOptions.classList.remove('hidden');
        toggleIcon.style.transform = 'rotate(0deg)';
    } else {
        filterOptions.classList.add('hidden');
        toggleIcon.style.transform = 'rotate(-90deg)';
    }
}

// Event Listeners

// Modal event listeners
document.addEventListener('click', function(e) {
    const modal = document.getElementById('notes-modal');
    if (e.target === modal) {
        closeNotesModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('notes-modal').classList.contains('show')) {
        closeNotesModal();
    }
});

// Drag and drop event listeners
document.addEventListener('dragstart', function(e) {
    console.log('Drag start detected on:', e.target);
    console.log('Closest column-drag-handle:', e.target.closest('.column-drag-handle'));
    console.log('Closest lead-card:', e.target.closest('.lead-card'));

    // Check if we're dragging a column (from the drag handle)
    if (e.target.closest('.column-drag-handle')) {
        const column = e.target.closest('.disposition-column');
        draggedColumn = column;
        column.classList.add('column-dragging');

        console.log('Column drag started:', column.dataset.disposition);

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

        console.log('Lead drag started:', {
            leadId: draggedLeadId,
            fromDisposition: draggedFromDisposition
        });

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
    console.log('Drop event detected');
    console.log('draggedColumn:', draggedColumn);

    // Handle column reordering
    if (draggedColumn) {
        console.log('Handling column drop');
        const targetColumn = e.target.closest('.disposition-column');
        console.log('Target column:', targetColumn);

        if (targetColumn && targetColumn !== draggedColumn) {
            const rect = targetColumn.getBoundingClientRect();
            const midpoint = rect.left + rect.width / 2;
            const isAfter = e.clientX > midpoint;

            console.log('Reordering columns:', {
                draggedColumn: draggedColumn.dataset.disposition,
                targetColumn: targetColumn.dataset.disposition,
                isAfter: isAfter
            });

            const container = document.getElementById('disposition-columns');
            if (isAfter) {
                container.insertBefore(draggedColumn, targetColumn.nextSibling);
            } else {
                container.insertBefore(draggedColumn, targetColumn);
            }

            saveColumnOrder();
            console.log('Column reordered successfully');
        }
        hideDropIndicator();
        return;
    }

    // Handle lead drops
    const dropZone = e.target.closest('.drop-zone');
    if (dropZone) {
        dropZone.classList.remove('drag-over');

        const newDisposition = dropZone.dataset.disposition;

        console.log('Drop detected:', {
            leadId: draggedLeadId,
            newDisposition,
            fromDisposition: draggedFromDisposition
        });

        // Don't do anything if dropped in same disposition
        if (newDisposition === draggedFromDisposition) {
            console.log('Dropped in same disposition, no action needed');
            return;
        }

        // Update lead disposition via Livewire
        if (draggedLeadId && newDisposition) {
            console.log('Calling Livewire method...');

            // Use the wire directive to call the method
            window.Livewire.find(window.livewireComponentId).call('updateLeadDisposition', draggedLeadId,
                newDisposition);
        }
    }
});

// Livewire event listeners
document.addEventListener('livewire:update', function() {
    console.log('livewire:update event fired');
    setTimeout(() => {
        console.log('Livewire update detected, reinitializing...');

        initializeDragAndDrop();
        initializeFilters();
    }, 100);
});

document.addEventListener('livewire:load', function() {
    console.log('livewire:load event fired');
});

document.addEventListener('livewire:updated', function() {
    console.log('livewire:updated event fired');
    setTimeout(() => {
        console.log('Livewire updated detected, reinitializing...');

        initializeDragAndDrop();
        initializeFilters();
    }, 100);
});

// Alpine.js mutations backup
if (window.Alpine) {
    window.Alpine.nextTick(() => {
        console.log('Alpine nextTick after potential Livewire update');
        setTimeout(() => {
            const currentOrder = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
                col.dataset.disposition
            );
            const savedOrder = localStorage.getItem('leadboard-column-order');

            if (savedOrder) {
                const parsedOrder = JSON.parse(savedOrder);
                if (JSON.stringify(currentOrder) !== JSON.stringify(parsedOrder)) {
                    console.log('Column order mismatch detected, reapplying...');
                    columnOrder = parsedOrder;
                    applyColumnOrder();
                }
            }
        }, 200);
    });
}

// Window resize listener
window.addEventListener('resize', function() {
    setTimeout(() => {
        updateScrollIndicator();
    }, 100);
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();
    initializeFilters();

    // Add scroll listener to columns container
    const columnsContainer = document.getElementById('disposition-columns');
    if (columnsContainer) {
        columnsContainer.addEventListener('scroll', function() {
            updateScrollNavigation();
        });
    }

    // Set up MutationObserver to watch for DOM changes
    const observer = new MutationObserver(function(mutations) {
        let shouldReinitialize = false;

        mutations.forEach(function(mutation) {
            // Check if disposition columns were added/removed/changed
            if (mutation.type === 'childList') {
                const target = mutation.target;
                if (target.id === 'disposition-columns' || target.closest('#disposition-columns')) {
                    console.log('DOM mutation detected in disposition columns');
                    shouldReinitialize = true;
                }
            }
        });

        if (shouldReinitialize) {
            setTimeout(() => {
                console.log('Reinitializing due to DOM mutation...');

                // Check if column order needs to be preserved
                const currentOrder = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
                    col.dataset.disposition
                );
                const savedOrder = localStorage.getItem('leadboard-column-order');

                if (savedOrder) {
                    const parsedOrder = JSON.parse(savedOrder);
                    if (JSON.stringify(currentOrder) !== JSON.stringify(parsedOrder)) {
                        console.log('Column order mismatch after mutation, reapplying...');
                        columnOrder = parsedOrder;
                        applyColumnOrder();
                    }
                }

                initializeDragAndDrop();
                updateScrollIndicator();
            }, 150);
        }
    });

    // Start observing
    const targetNode = document.getElementById('disposition-columns');
    if (targetNode) {
        observer.observe(targetNode, {
            childList: true,
            subtree: true
        });
        console.log('MutationObserver started for disposition columns');
    }
});
