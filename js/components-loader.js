// ===================================
// Component Loader
// Load HTML components dynamically
// ===================================

async function loadComponent(elementId, componentPath) {
    try {
        const response = await fetch(componentPath);
        if (!response.ok) {
            throw new Error(`Failed to load ${componentPath}: ${response.status}`);
        }
        const html = await response.text();
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = html;
        } else {
            console.error(`Element with id '${elementId}' not found`);
        }
    } catch (error) {
        console.error('Error loading component:', error);
    }
}

// Load all components when DOM is ready
document.addEventListener('DOMContentLoaded', async function() {
    // Load components in sequence
    await loadComponent('sidebar-container', 'components/sidebar.html');
    await loadComponent('header-container', 'components/header.html');
    await loadComponent('dashboard-container', 'components/dashboard.html');
    
    // Initialize the main script after components are loaded
    if (typeof initializeApp === 'function') {
        initializeApp();
    }
});
