// ===================================
// Enhanced Table.js - Reusable Table Functionality
// ===================================

class EnhancedTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        if (!this.table) {
            console.error(`Table with id "${tableId}" not found`);
            return;
        }
        
        this.tbody = this.table.querySelector('tbody');
        this.thead = this.table.querySelector('thead');
        this.options = {
            sortable: options.sortable !== false,
            searchable: options.searchable !== false,
            paginated: options.paginated !== false,
            pageSize: options.pageSize || 10,
            responsive: options.responsive !== false,
            defaultFilter: options.defaultFilter || null,
            ...options
        };
        
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.allRows = [];
        this.filteredRows = [];
        
        this.init();
    }
    
    init() {
        // Store all rows
        this.allRows = Array.from(this.tbody.querySelectorAll('tr'));

        // Apply defaultFilter for initial display (e.g. hide deceased)
        if (this.options.defaultFilter) {
            this.filteredRows = this.allRows.filter(this.options.defaultFilter);
        } else {
            this.filteredRows = [...this.allRows];
        }
        
        // Initialize features
        if (this.options.sortable) {
            this.initSorting();
        }
        
        if (this.options.paginated) {
            this.updateDisplay();
            this.updatePagination();
        }
        
        // Add row click handlers
        this.initRowHandlers();
    }
    
    // ===================================
    // Sorting Functionality
    // ===================================
    initSorting() {
        const headers = this.thead.querySelectorAll('th');
        headers.forEach((header, index) => {
            // Skip action column or non-sortable columns
            if (header.classList.contains('no-sort') || 
                header.textContent.trim().toLowerCase() === 'action') {
                return;
            }

            // Guard: skip if sort icon already added (prevents duplicates on re-init)
            if (header.querySelector('.sort-icon')) {
                return;
            }
            
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            header.classList.add('sortable');
            
            // Add sort icon
            const sortIcon = document.createElement('i');
            sortIcon.className = 'fas fa-sort sort-icon';
            sortIcon.style.marginLeft = '8px';
            sortIcon.style.fontSize = '12px';
            sortIcon.style.color = '#9ca3af';
            header.appendChild(sortIcon);
            
            header.addEventListener('click', () => {
                this.sortTable(index, header);
            });
        });
    }
    
    sortTable(columnIndex, header) {
        const headers = this.thead.querySelectorAll('th');
        
        // Update sort direction
        if (this.sortColumn === columnIndex) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortDirection = 'asc';
            this.sortColumn = columnIndex;
        }

        // Reset ALL sort icons first, then set only the active one
        headers.forEach(h => {
            const icon = h.querySelector('.sort-icon');
            if (icon) {
                icon.className = 'fas fa-sort sort-icon';
                icon.style.color = '#9ca3af';
            }
        });

        // Set active icon on the clicked column only
        const activeIcon = headers[columnIndex]?.querySelector('.sort-icon');
        if (activeIcon) {
            activeIcon.className = this.sortDirection === 'asc'
                ? 'fas fa-sort-up sort-icon'
                : 'fas fa-sort-down sort-icon';
            activeIcon.style.color = '#3b82f6';
        }
        
        // Sort the filtered rows
        this.filteredRows.sort((a, b) => {
            const aValue = this.getCellValue(a, columnIndex);
            const bValue = this.getCellValue(b, columnIndex);
            
            // Handle different data types
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison (locale-aware, case-insensitive)
            const comparison = aValue.localeCompare(bValue, undefined, { numeric: true, sensitivity: 'base' });
            return this.sortDirection === 'asc' ? comparison : -comparison;
        });
        
        // Reset to first page and update display
        this.currentPage = 1;
        this.updateDisplay();
    }

    /**
     * Programmatically sort by a column index (used for default sort on init).
     */
    sortByColumn(columnIndex) {
        const headers = this.thead.querySelectorAll('th');
        if (headers[columnIndex]) {
            this.sortTable(columnIndex, headers[columnIndex]);
        }
    }
    
    getCellValue(row, columnIndex) {
        const cell = row.cells[columnIndex];
        if (!cell) return '';

        // 1. Prefer explicit data-sort attribute (most reliable)
        const dataSortValue = cell.getAttribute('data-sort');
        if (dataSortValue !== null) return dataSortValue.trim();
        
        // 2. Badge content
        const badge = cell.querySelector('.badge');
        if (badge) return badge.textContent.trim();
        
        // 3. Last span inside the cell (e.g. resident name span)
        const spans = cell.querySelectorAll('span');
        if (spans.length > 0) return spans[spans.length - 1].textContent.trim();
        
        return cell.textContent.trim();
    }
    
    // ===================================
    // Search/Filter Functionality
    // ===================================
    search(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();
        
        if (!searchTerm) {
            // No search term — apply defaultFilter (e.g. hide deceased)
            if (this.options.defaultFilter) {
                this.filteredRows = this.allRows.filter(this.options.defaultFilter);
            } else {
                this.filteredRows = [...this.allRows];
            }
        } else {
            // Active search — search ALL rows including those hidden by defaultFilter
            // so deceased residents are still findable by name
            this.filteredRows = this.allRows.filter(row => {
                const cells = Array.from(row.cells);
                return cells.some(cell => {
                    const text = cell.textContent.toLowerCase();
                    return text.includes(searchTerm);
                });
            });
        }
        
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
    }
    
    filter(filterFn) {
        // Apply custom filter, then also apply defaultFilter on top
        let rows = this.allRows.filter(filterFn);
        if (this.options.defaultFilter) {
            rows = rows.filter(this.options.defaultFilter);
        }
        this.filteredRows = rows;
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
    }
    
    // ===================================
    // Pagination Functionality
    // ===================================
    updatePagination() {
        const totalRows = this.filteredRows.length;
        const totalPages = Math.ceil(totalRows / this.options.pageSize);
        
        // Update pagination info
        const paginationInfo = document.querySelector('.pagination-info');
        if (paginationInfo) {
            paginationInfo.innerHTML = `
                <span>Showing <strong>${this.getStartRow()}-${this.getEndRow()}</strong> of <strong>${totalRows}</strong></span>
            `;
        }
        
        // Update pagination buttons
        const pagination = document.querySelector('.pagination');
        if (pagination) {
            this.renderPaginationButtons(pagination, totalPages);
        }
    }
    
    renderPaginationButtons(container, totalPages) {
        container.innerHTML = '';
        
        // Previous button
        const prevBtn = this.createPageButton('prev', this.currentPage > 1);
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        container.appendChild(prevBtn);
        
        // Page numbers
        const pageNumbers = this.getPageNumbers(totalPages);
        pageNumbers.forEach(page => {
            if (page === '...') {
                const dots = document.createElement('span');
                dots.className = 'page-dots';
                dots.textContent = '...';
                container.appendChild(dots);
            } else {
                const pageBtn = this.createPageButton(page, true, page === this.currentPage);
                pageBtn.textContent = page;
                pageBtn.addEventListener('click', () => this.goToPage(page));
                container.appendChild(pageBtn);
            }
        });
        
        // Next button
        const nextBtn = this.createPageButton('next', this.currentPage < totalPages);
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.addEventListener('click', () => this.goToPage(this.currentPage + 1));
        container.appendChild(nextBtn);
    }
    
    createPageButton(text, enabled, active = false) {
        const btn = document.createElement('button');
        btn.className = 'page-btn';
        if (active) btn.classList.add('active');
        if (!enabled) btn.disabled = true;
        return btn;
    }
    
    getPageNumbers(totalPages) {
        const pages = [];
        const current = this.currentPage;
        
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                pages.push(i);
            }
        } else {
            if (current <= 3) {
                for (let i = 1; i <= 5; i++) pages.push(i);
                pages.push('...');
                pages.push(totalPages);
            } else if (current >= totalPages - 2) {
                pages.push(1);
                pages.push('...');
                for (let i = totalPages - 4; i <= totalPages; i++) pages.push(i);
            } else {
                pages.push(1);
                pages.push('...');
                for (let i = current - 1; i <= current + 1; i++) pages.push(i);
                pages.push('...');
                pages.push(totalPages);
            }
        }
        
        return pages;
    }
    
    goToPage(page) {
        const totalPages = Math.ceil(this.filteredRows.length / this.options.pageSize);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.updateDisplay();
        this.updatePagination();
        
        // Smooth scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    getStartRow() {
        return Math.min((this.currentPage - 1) * this.options.pageSize + 1, this.filteredRows.length);
    }
    
    getEndRow() {
        return Math.min(this.currentPage * this.options.pageSize, this.filteredRows.length);
    }
    
    // ===================================
    // Display Update
    // ===================================
    updateDisplay() {
        // Clear current display
        this.tbody.innerHTML = '';
        
        // Calculate which rows to show
        const start = (this.currentPage - 1) * this.options.pageSize;
        const end = start + this.options.pageSize;
        const rowsToShow = this.filteredRows.slice(start, end);
        
        // Add rows to display
        if (rowsToShow.length === 0) {
            this.showNoResults();
        } else {
            rowsToShow.forEach(row => {
                this.tbody.appendChild(row);
            });
        }
    }
    
    showNoResults() {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = this.thead.querySelectorAll('th').length;
        cell.style.textAlign = 'center';
        cell.style.padding = '40px';
        cell.style.color = '#6b7280';
        cell.innerHTML = `
            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
            <p style="font-size: 16px; font-weight: 500;">No results found</p>
            <p style="font-size: 14px; margin-top: 5px;">Try adjusting your search or filter criteria</p>
        `;
        row.appendChild(cell);
        this.tbody.appendChild(row);
    }
    
    // ===================================
    // Row Handlers
    // ===================================
    initRowHandlers() {
        // Add hover effects
        this.tbody.addEventListener('mouseenter', (e) => {
            if (e.target.tagName === 'TR') {
                e.target.style.transform = 'scale(1.001)';
                e.target.style.transition = 'transform 0.2s ease';
            }
        }, true);
        
        this.tbody.addEventListener('mouseleave', (e) => {
            if (e.target.tagName === 'TR') {
                e.target.style.transform = 'scale(1)';
            }
        }, true);
    }
    
    // ===================================
    // Export Functionality
    // ===================================
    exportToCSV(filename = 'table-export.csv') {
        const headers = Array.from(this.thead.querySelectorAll('th'))
            .map(th => th.textContent.trim().replace(/\s+/g, ' '))
            .filter(text => !text.toLowerCase().includes('action'));
        
        const rows = this.filteredRows.map(row => {
            return Array.from(row.cells)
                .slice(0, -1) // Exclude action column
                .map(cell => {
                    let text = cell.textContent.trim().replace(/\s+/g, ' ');
                    // Escape quotes and wrap in quotes if contains comma
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    return text;
                });
        });
        
        const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
        
        // Download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }
    
    // ===================================
    // Utility Methods
    // ===================================
    refresh() {
        this.allRows = Array.from(this.tbody.querySelectorAll('tr'));
        this.filteredRows = [...this.allRows];
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
    }
    
    getTotalRows() {
        return this.allRows.length;
    }
    
    getFilteredRows() {
        return this.filteredRows.length;
    }
    
    reset() {
        // Apply defaultFilter when resetting (don't show deceased on reset)
        if (this.options.defaultFilter) {
            this.filteredRows = this.allRows.filter(this.options.defaultFilter);
        } else {
            this.filteredRows = [...this.allRows];
        }
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        
        // Reset sort icons
        this.thead.querySelectorAll('th .sort-icon').forEach(icon => {
            icon.className = 'fas fa-sort sort-icon';
            icon.style.color = '#9ca3af';
        });
        
        this.updateDisplay();
        this.updatePagination();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedTable;
}
