# Sidebar Fix - Empty Space Issue

## Problem
When clicking "Resident Records", a large empty space appears between the Settings nav item and the sidebar footer. This does NOT happen when "Barangay Info" is active (Settings submenu is open).

## Root Cause
`.sidebar-nav` has `flex: 1` which forces it to fill ALL remaining space in the sidebar regardless of content length. When the Settings submenu is closed, the nav content is shorter but the nav area still expands — creating a large empty gap.

## Steps

- [x] Analyze sidebar.php, style.css, and script.js
- [x] Identify root cause
- [x] Create plan and get user approval
- [x] Fix `.sidebar-nav` in assets/css/style.css — remove `flex: 1` and `overflow-y: auto`
- [x] Verify fix in browser
