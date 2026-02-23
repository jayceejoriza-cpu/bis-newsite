/**
 * Dark Mode Initialization Script
 * 
 * This script MUST be loaded in <head> BEFORE any CSS or other scripts.
 * It prevents the Flash of Unstyled Content (FOUC) by:
 *   1. Immediately disabling all CSS transitions (no-transition class)
 *   2. Applying dark-mode class to <html> before the page renders
 *   3. Re-enabling transitions after the page has fully painted
 */
(function () {
    // Step 1: Disable all transitions to prevent light→dark animation on load
    document.documentElement.classList.add('no-transition');

    // Step 2: Apply dark mode immediately if user preference is dark
    try {
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    } catch (e) {
        // localStorage may be unavailable in some contexts — fail silently
    }

    // Step 3: Re-enable transitions after the first paint is complete
    // Double requestAnimationFrame ensures the browser has painted at least one frame
    window.addEventListener('load', function () {
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.documentElement.classList.remove('no-transition');
            });
        });
    });
})();
