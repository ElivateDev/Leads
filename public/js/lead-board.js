// Lead Board JavaScript Functionality

let draggedElement = null;
let draggedLeadId = null;
let draggedFromDisposition = null;
let visibleDispositions = [];
let columnOrder = [];
let draggedColumn = null;
let dropIndicator = null;
let currentLeadId = null;
let filterPanelOpen = false; // Pure client-side state for filter panel

// Helper function to safely call Livewire methods
function safeLivewireCall(method, ...args) {
    try {
        if (typeof Livewire === 'undefined' || !Livewire.find) {
            console.warn('Livewire not available, skipping call to:', method);
            return false;
        }

        const wireElement = document.querySelector('[wire\\:id]');
        if (!wireElement) {
            console.warn('No Livewire component found in DOM');
            return false;
        }

        const wireId = wireElement.getAttribute('wire:id');
        const component = Livewire.find(wireId);

        if (!component || !component.call) {
            console.warn('Livewire component not found or method not available');
            return false;
        }

        component.call(method, ...args);
        return true;
    } catch (error) {
        console.warn(`Failed to call Livewire method ${method}:`, error);
        return false;
    }
}

// Wait for Livewire to be ready
function waitForLivewire(callback, maxAttempts = 50) {
    let attempts = 0;

    function checkLivewire() {
        attempts++;

        if (typeof Livewire !== 'undefined' && Livewire.find) {
            const wireElement = document.querySelector('[wire\\:id]');
            if (wireElement) {
                const wireId = wireElement.getAttribute('wire:id');
                const component = Livewire.find(wireId);

                if (component && component.call) {
                    callback();
                    return;
                }
            }
        }

        if (attempts < maxAttempts) {
            setTimeout(checkLivewire, 100); // Check every 100ms
        } else {
            console.warn('Livewire not ready after maximum attempts, proceeding without it');
            callback();
        }
    }

    checkLivewire();
}

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
    safeLivewireCall('updateLeadNotes', currentLeadId, notes);

    closeNotesModal();
}

// Initialize drag and drop functionality
function initializeDragAndDrop() {
    // Make sure all lead cards are draggable
    document.querySelectorAll('.lead-card').forEach(card => {
        card.draggable = true;
    });
}

// Initialize filter functionality
function initializeFilters() {
    // Get saved filter panel state from localStorage (default to expanded since HTML defaults to expanded)
    const savedPanelState = localStorage.getItem('leadboard-filter-panel-open');
    filterPanelOpen = savedPanelState !== 'false'; // Default to true (expanded) if no saved state

    // Apply the saved panel state to the UI (since HTML defaults to expanded, we only need to collapse if saved state is false)
    const filterOptions = document.getElementById('filter-options');
    const toggleIcon = document.querySelector('.filter-toggle-icon');

    if (!filterPanelOpen) {
        // Only collapse if the saved state is false
        filterOptions.style.display = 'none';
        if (toggleIcon) toggleIcon.style.transform = 'rotate(-90deg)';
    }
    // If filterPanelOpen is true, we don't need to do anything as HTML defaults to expanded

    // Prioritize localStorage (immediate) over database (delayed), then use database as fallback
    const saved = localStorage.getItem('leadboard-visible-dispositions');
    if (saved) {
        visibleDispositions = JSON.parse(saved);
    } else if (window.initialVisibleDispositions) {
        visibleDispositions = window.initialVisibleDispositions;
    } else {
        // Default to all dispositions visible
        visibleDispositions = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
            col.dataset.disposition
        );
    }

    // Load column order from database or localStorage
    initializeColumnOrder();

    // Initialize filter event listeners
    initializeFilterEventListeners();

    // Apply filter display and column visibility immediately (no database calls during init)
    updateFilterDisplay();
    updateColumnVisibilityOnly();
    updateScrollIndicator();

    // Set up a MutationObserver to restore filter state when DOM changes
    initializeFilterStateProtection();
}

// Initialize column ordering
function initializeColumnOrder() {
    // Prioritize localStorage (immediate) over database (delayed), then use database as fallback
    const savedOrder = localStorage.getItem('leadboard-column-order');
    if (savedOrder) {
        columnOrder = JSON.parse(savedOrder);
        applyColumnOrder();
    } else if (window.initialColumnOrder) {
        columnOrder = window.initialColumnOrder;
        applyColumnOrder();
    } else {
        // Default order based on current DOM order
        columnOrder = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
            col.dataset.disposition
        );
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
}

// Save column order to localStorage and database
// Save column order to localStorage and database
function saveColumnOrder() {
    const columns = Array.from(document.querySelectorAll('.disposition-column'));
    columnOrder = columns.map(col => col.dataset.disposition);

    // Save to localStorage for immediate feedback
    localStorage.setItem('leadboard-column-order', JSON.stringify(columnOrder));

    // Save to database via Livewire
    safeLivewireCall('updateColumnOrder', columnOrder);
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

        // Disabled scroll indicator classes to prevent fade effect
        // if (hasScroll) {
        //     columnsContainer.classList.add('has-scroll');
        // } else {
        //     columnsContainer.classList.remove('has-scroll');
        // }

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
            // Explicitly remove the checked class to ensure it's unchecked
            checkbox.classList.remove('checked');
        }
    });
}

// Update column visibility based on filters (without database save)
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

    // Save to localStorage immediately for instant persistence
    localStorage.setItem('leadboard-visible-dispositions', JSON.stringify(visibleDispositions));

    updateFilterDisplay();
    updateColumnVisibilityOnly();

    // Don't save to database automatically - only on panel close or page unload
    // saveFilterStateToDatabase();
}

// Select all dispositions
function selectAllDispositions() {
    visibleDispositions = Array.from(document.querySelectorAll('.disposition-column')).map(col =>
        col.dataset.disposition
    );

    // Save to localStorage immediately for instant persistence
    localStorage.setItem('leadboard-visible-dispositions', JSON.stringify(visibleDispositions));

    updateFilterDisplay();
    updateColumnVisibilityOnly();

    // Don't save to database automatically - only on page unload
    // saveFilterStateToDatabase();
}

// Select no dispositions
function selectNoneDispositions() {
    visibleDispositions = [];

    // Save to localStorage immediately for instant persistence
    localStorage.setItem('leadboard-visible-dispositions', JSON.stringify(visibleDispositions));

    updateFilterDisplay();
    updateColumnVisibilityOnly();

    // Don't save to database automatically - only on page unload
    // saveFilterStateToDatabase();
}

// Save filter state to database (delayed, non-blocking)
function saveFilterStateToDatabase() {
    // Use a debounced approach to avoid too many database calls
    if (saveFilterStateToDatabase.timeout) {
        clearTimeout(saveFilterStateToDatabase.timeout);
    }

    saveFilterStateToDatabase.timeout = setTimeout(() => {
        safeLivewireCall('updateVisibleDispositions', visibleDispositions);
    }, 2000); // Save 2 seconds after last change
}

// Toggle filter panel - pure client-side, no Livewire needed
function toggleFilterPanel() {
    const filterOptions = document.getElementById('filter-options');
    const toggleIcon = document.querySelector('.filter-toggle-icon');

    // Toggle our client-side state
    filterPanelOpen = !filterPanelOpen;

    // Update the UI based on our state
    if (filterPanelOpen) {
        filterOptions.style.display = 'grid';
        if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';

        // Save state to localStorage for persistence
        localStorage.setItem('leadboard-filter-panel-open', 'true');
    } else {
        filterOptions.style.display = 'none';
        if (toggleIcon) toggleIcon.style.transform = 'rotate(-90deg)';

        // Save state to localStorage for persistence
        localStorage.setItem('leadboard-filter-panel-open', 'false');

        // Don't save filter state to database when panel closes - only on page unload
        // saveFilterStateToDatabase();
    }
}

// Initialize filter event listeners
function initializeFilterEventListeners() {
    // Simple event delegation approach
    const filterPanel = document.querySelector('.filter-panel');
    if (!filterPanel) return;

    // Remove any existing click listener on filter panel
    filterPanel.removeEventListener('click', handleFilterPanelClick);

    // Add single click listener to filter panel
    filterPanel.addEventListener('click', handleFilterPanelClick);
}

// Protect filter state from DOM changes
function initializeFilterStateProtection() {
    // Watch for changes to the filter panel that might reset our state
    const filterPanel = document.querySelector('.filter-panel');
    if (!filterPanel) return;

    const observer = new MutationObserver(function(mutations) {
        let shouldRestoreState = false;

        mutations.forEach(function(mutation) {
            // Check if filter checkboxes were modified
            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                const target = mutation.target;
                if (target.classList && (target.classList.contains('filter-panel') ||
                    target.classList.contains('filter-options') ||
                    target.classList.contains('filter-checkbox'))) {
                    shouldRestoreState = true;
                }
            }
        });

        if (shouldRestoreState) {
            // Restore filter state after a brief delay
            setTimeout(() => {
                // Restore filter selections
                updateFilterDisplay();
                updateColumnVisibilityOnly();
                
                // Restore panel open/closed state
                const filterOptions = document.getElementById('filter-options');
                const toggleIcon = document.querySelector('.filter-toggle-icon');
                
                if (filterPanelOpen) {
                    filterOptions.style.display = 'grid';
                    if (toggleIcon) toggleIcon.style.transform = 'rotate(0deg)';
                } else {
                    filterOptions.style.display = 'none';
                    if (toggleIcon) toggleIcon.style.transform = 'rotate(-90deg)';
                }
            }, 10);
        }
    });

    // Observe the entire filter panel for changes
    observer.observe(filterPanel, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class']
    });
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

        // No need to worry about panel state - it's managed purely client-side now
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
            // Use the wire directive to call the method
            safeLivewireCall('updateLeadDisposition', draggedLeadId, newDisposition);
        }
    }
});

// Livewire event listeners - only reinitialize drag and drop, filters are pure client-side
document.addEventListener('livewire:updated', function() {
    setTimeout(() => {
        initializeDragAndDrop();
        updateScrollIndicator();

        // Reapply filter states without reinitializing the whole filter system
        updateFilterDisplay();
        updateColumnVisibilityOnly();
    }, 100);
});

// Window resize listener
window.addEventListener('resize', function() {
    setTimeout(() => {
        updateScrollIndicator();
    }, 100);
});

// Save filter state when page unloads
window.addEventListener('beforeunload', function() {
    // Clear any pending timeout and save immediately
    if (saveFilterStateToDatabase.timeout) {
        clearTimeout(saveFilterStateToDatabase.timeout);
    }
    safeLivewireCall('updateVisibleDispositions', visibleDispositions);
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeDragAndDrop();

    // Initialize filters (this will wait for Livewire for database calls)
    initializeFilters();

    // Add scroll listener to columns container
    const columnsContainer = document.getElementById('disposition-columns');
    if (columnsContainer) {
        columnsContainer.addEventListener('scroll', function() {
            updateScrollNavigation();
        });
    }
});
