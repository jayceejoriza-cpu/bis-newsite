// ===================================
// Grid Pagination - Reusable Grid Pagination Functionality
// ===================================

class GridPagination {
    constructor(gridId, options = {}) {
        this.grid = document.getElementById(gridId);
        if (!this.grid) {
            console.error(`Grid with id "${gridId}" not found`);
            return;
        }
        
        this.options = {
            pageSize: options.pageSize || 12,
            ...options
        };
        
        this.currentPage = 1;
        this.allItems = [];
        this.filteredItems = [];
        
        this.init();
    }
    
    init() {
        this.allItems = Array.from(this.grid.children);
        this.filteredItems = [...this.allItems];
        this.updateDisplay();
        this.updatePagination();
    }
    
    // ===================================
    // Search/Filter Functionality
    // ===================================
    search(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();
        
        if (!searchTerm) {
            this.filteredItems = [...this.allItems];
        } else {
            this.filteredItems = this.allItems.filter(item => {
                const text = item.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
        }
        
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
    }
    
    filter(filterFn) {
        this.filteredItems = this.allItems.filter(filterFn);
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
    }
    
    // ===================================
    // Pagination Functionality
    // ===================================
    updatePagination() {
        const totalItems = this.filteredItems.length;
        const totalPages = Math.ceil(totalItems / this.options.pageSize);
        
        const paginationInfo = document.querySelector('.pagination-info');
        if (paginationInfo) {
            paginationInfo.innerHTML = `
                <span>Showing <strong>${this.getStartItem()}-${this.getEndItem()}</strong> of <strong>${totalItems}</strong></span>
            `;
        }
        
        const pagination = document.querySelector('.pagination');
        if (pagination) {
            this.renderPaginationButtons(pagination, totalPages);
        }
    }
    
    renderPaginationButtons(container, totalPages) {
        container.innerHTML = '';
        
        const prevBtn = this.createPageButton('prev', this.currentPage > 1);
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        container.appendChild(prevBtn);
        
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
        const totalPages = Math.ceil(this.filteredItems.length / this.options.pageSize);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.updateDisplay();
        this.updatePagination();
    }
    
    getStartItem() {
        return Math.min((this.currentPage - 1) * this.options.pageSize + 1, this.filteredItems.length);
    }
    
    getEndItem() {
        return Math.min(this.currentPage * this.options.pageSize, this.filteredItems.length);
    }
    
    // ===================================
    // Display Update
    // ===================================
    updateDisplay() {
        this.allItems.forEach(item => item.style.display = 'none');
        
        const start = (this.currentPage - 1) * this.options.pageSize;
        const end = start + this.options.pageSize;
        const itemsToShow = this.filteredItems.slice(start, end);
        
        if (itemsToShow.length === 0) {
            this.showNoResults();
        } else {
            itemsToShow.forEach(item => {
                item.style.display = 'flex';
            });
        }
    }
    
    showNoResults() {
        let noResults = this.grid.querySelector('.no-results');
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.style.textAlign = 'center';
            noResults.style.padding = '40px';
            noResults.style.color = '#6b7280';
            noResults.innerHTML = `
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p style="font-size: 16px; font-weight: 500;">No results found</p>
                <p style="font-size: 14px; margin-top: 5px;">Try adjusting your search or filter criteria</p>
            `;
            this.grid.appendChild(noResults);
        }
        noResults.style.display = 'block';
    }

    // ===================================
    // Utility Methods
    // ===================================
    reset() {
        this.filteredItems = [...this.allItems];
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
        
        let noResults = this.grid.querySelector('.no-results');
        if(noResults) {
            noResults.style.display = 'none';
        }
    }

    refresh() {
        this.allItems = Array.from(this.grid.children).filter(child => child.classList.contains('resident-card'));
        this.filteredItems = [...this.allItems];
        this.currentPage = 1;
        this.updateDisplay();
        this.updatePagination();
    }
}
