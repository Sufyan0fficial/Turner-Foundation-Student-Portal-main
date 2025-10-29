// Responsive Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Add viewport meta tag if not present
    if (!document.querySelector('meta[name="viewport"]')) {
        const viewport = document.createElement('meta');
        viewport.name = 'viewport';
        viewport.content = 'width=device-width, initial-scale=1.0';
        document.head.appendChild(viewport);
    }
    
    // Initialize responsive tables
    initResponsiveTables();
    
    // Initialize mobile navigation
    initMobileNavigation();
    
    // Handle window resize
    window.addEventListener('resize', handleResize);
    
    // Initial resize check
    handleResize();
});

function initResponsiveTables() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        if (!table.closest('.responsive-table')) {
            // Wrap table in responsive container
            const wrapper = document.createElement('div');
            wrapper.className = 'responsive-table';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        // Create mobile cards version
        createMobileCards(table);
    });
}

function createMobileCards(table) {
    const rows = table.querySelectorAll('tbody tr');
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    
    if (rows.length === 0 || headers.length === 0) return;
    
    const mobileContainer = document.createElement('div');
    mobileContainer.className = 'mobile-cards';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return;
        
        const card = document.createElement('div');
        card.className = 'mobile-card';
        
        // Card header with primary info
        const cardHeader = document.createElement('div');
        cardHeader.className = 'mobile-card-header';
        
        const cardTitle = document.createElement('div');
        cardTitle.className = 'mobile-card-title';
        cardTitle.textContent = cells[0] ? cells[0].textContent.trim() : '';
        
        const cardMeta = document.createElement('div');
        cardMeta.className = 'mobile-card-meta';
        cardMeta.textContent = cells[1] ? cells[1].textContent.trim() : '';
        
        cardHeader.appendChild(cardTitle);
        cardHeader.appendChild(cardMeta);
        
        // Card content with remaining fields
        const cardContent = document.createElement('div');
        cardContent.className = 'mobile-card-content';
        
        for (let i = 2; i < Math.min(cells.length, headers.length); i++) {
            const field = document.createElement('div');
            field.className = 'mobile-card-field';
            
            const label = document.createElement('div');
            label.className = 'mobile-card-label';
            label.textContent = headers[i];
            
            const value = document.createElement('div');
            value.className = 'mobile-card-value';
            value.innerHTML = cells[i].innerHTML;
            
            field.appendChild(label);
            field.appendChild(value);
            cardContent.appendChild(field);
        }
        
        card.appendChild(cardHeader);
        card.appendChild(cardContent);
        mobileContainer.appendChild(card);
    });
    
    // Insert mobile cards after the table wrapper
    const tableWrapper = table.closest('.responsive-table');
    tableWrapper.parentNode.insertBefore(mobileContainer, tableWrapper.nextSibling);
    
    // Add desktop-table class to original table wrapper
    tableWrapper.classList.add('desktop-table');
}

function initMobileNavigation() {
    // Add hamburger menu for very small screens if needed
    const nav = document.querySelector('.admin-nav');
    if (!nav) return;
    
    // Make navigation scrollable on mobile
    nav.style.overflowX = 'auto';
    nav.style.webkitOverflowScrolling = 'touch';
}

function handleResize() {
    const isMobile = window.innerWidth < 768;
    
    // Toggle table/card visibility
    document.querySelectorAll('.desktop-table').forEach(table => {
        table.style.display = isMobile ? 'none' : 'block';
    });
    
    document.querySelectorAll('.mobile-cards').forEach(cards => {
        cards.style.display = isMobile ? 'block' : 'none';
    });
    
    // Adjust modal sizes
    document.querySelectorAll('.modal-content').forEach(modal => {
        if (isMobile) {
            modal.style.width = '95%';
            modal.style.maxWidth = 'none';
            modal.style.margin = '10px';
        } else {
            modal.style.width = '';
            modal.style.maxWidth = '';
            modal.style.margin = '';
        }
    });
}

// Utility functions for responsive behavior
function showMobileMenu() {
    const nav = document.querySelector('.admin-nav');
    if (nav) {
        nav.classList.toggle('mobile-menu-open');
    }
}

function closeMobileMenu() {
    const nav = document.querySelector('.admin-nav');
    if (nav) {
        nav.classList.remove('mobile-menu-open');
    }
}

// Touch-friendly interactions
function addTouchSupport() {
    // Add touch classes for better mobile interaction
    document.addEventListener('touchstart', function(e) {
        const target = e.target.closest('.btn, .stat-card, .mobile-card, .nav-link');
        if (target) {
            target.classList.add('touch-active');
        }
    });
    
    document.addEventListener('touchend', function(e) {
        setTimeout(() => {
            document.querySelectorAll('.touch-active').forEach(el => {
                el.classList.remove('touch-active');
            });
        }, 150);
    });
}

// Initialize touch support
addTouchSupport();
