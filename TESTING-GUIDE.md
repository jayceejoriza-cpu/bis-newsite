# Residents Table - Testing Guide

## How to Test the Enhanced Table

### Prerequisites
1. Ensure XAMPP is running
2. Navigate to: `http://localhost/bis-newsite/bis-newsite/residents.php`

## Test Cases

### 1. Column Sorting ✓
**Test Steps:**
1. Click on any column header (except "Action")
2. Observe the sort icon changes to up arrow (↑)
3. Click again to reverse sort (down arrow ↓)
4. Click a different column to sort by that column

**Expected Results:**
- Sort icons appear on all sortable columns
- Data sorts correctly (alphabetically for text, numerically for numbers)
- Active sort column shows colored icon
- Inactive columns show gray sort icon

### 2. Search Functionality ✓
**Test Steps:**
1. Type in the search box (e.g., "Ladarius")
2. Wait 300ms for debounce
3. Observe filtered results
4. Click the X button to clear search

**Expected Results:**
- Table filters to show only matching rows
- Search works across all columns
- Pagination updates to show filtered count
- Clear button resets the search

### 3. Pagination ✓
**Test Steps:**
1. Click page numbers (1, 2, 3, etc.)
2. Click Previous/Next arrows
3. Observe page info updates

**Expected Results:**
- Shows 10 rows per page
- Page info shows "Showing X-Y of Z"
- Active page is highlighted in blue
- Previous button disabled on page 1
- Smooth scroll to top on page change

### 4. Filter Tabs ✓
**Test Steps:**
1. Click "All" tab - shows all residents
2. Click "Verified" tab - shows only verified residents
3. Click "Voters" tab - shows only voters
4. Click "Active" tab - shows only active residents

**Expected Results:**
- Active tab highlighted in blue
- Table filters correctly
- Pagination resets to page 1
- Count updates in pagination info

### 5. Action Menu ✓
**Test Steps:**
1. Click the three-dot button (⋯) on any row
2. Observe dropdown menu appears
3. Click "View Details" - shows alert
4. Click "Edit Resident" - shows alert
5. Click "Print ID" - shows alert
6. Click "Delete Resident" - shows confirmation

**Expected Results:**
- Menu appears below the button
- Menu has 4 options with icons
- Delete option is red
- Menu closes when clicking outside
- Actions show appropriate messages

### 6. Refresh Button ✓
**Test Steps:**
1. Click the refresh button (🔄)
2. Observe spinning animation
3. See success notification

**Expected Results:**
- Icon spins for 0.5 seconds
- Green notification appears top-right
- Notification auto-dismisses after 3 seconds
- Table data refreshes

### 7. Responsive Design ✓
**Test Steps:**
1. Resize browser window to mobile size
2. Test all features on mobile
3. Scroll horizontally if needed

**Expected Results:**
- Table remains functional
- Horizontal scroll appears if needed
- All buttons remain accessible
- Touch interactions work

### 8. Combined Features ✓
**Test Steps:**
1. Apply a filter tab (e.g., "Verified")
2. Search within filtered results
3. Sort the filtered, searched results
4. Navigate through pages

**Expected Results:**
- All features work together seamlessly
- Filters stack correctly
- Pagination reflects combined filters
- Sort maintains filter state

## Console Checks

Open browser console (F12) and verify:
- ✓ No JavaScript errors
- ✓ "Residents page loaded successfully" message
- ✓ "Total residents: 10" message
- ✓ Filter/sort actions logged correctly

## Visual Checks

### Sorting
- [ ] Sort icons visible on headers
- [ ] Active sort icon is blue
- [ ] Hover effect on sortable columns

### Search
- [ ] Search box has magnifying glass icon
- [ ] Clear button (X) appears when typing
- [ ] Debounce prevents excessive filtering

### Pagination
- [ ] Page numbers display correctly
- [ ] Active page highlighted
- [ ] Disabled buttons have reduced opacity
- [ ] Page info shows correct range

### Action Menu
- [ ] Menu appears with smooth animation
- [ ] Menu positioned correctly
- [ ] Icons aligned properly
- [ ] Delete option is red
- [ ] Hover effects work

### Notifications
- [ ] Appears top-right
- [ ] Green background for success
- [ ] Slides in from right
- [ ] Auto-dismisses after 3 seconds

## Performance Checks

- [ ] Search debounce works (300ms delay)
- [ ] Sorting is instant
- [ ] Pagination is smooth
- [ ] No lag when filtering
- [ ] Animations are smooth (60fps)

## Browser Compatibility

Test in multiple browsers:
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile Chrome
- [ ] Mobile Safari

## Common Issues & Solutions

### Issue: Sort icons not appearing
**Solution:** Ensure table.js is loaded before residents.js

### Issue: Pagination not working
**Solution:** Check that table has id="residentsTable"

### Issue: Search not filtering
**Solution:** Verify searchInput element exists with correct ID

### Issue: Action menu not appearing
**Solution:** Check that Font Awesome is loaded

### Issue: Console errors
**Solution:** Check script loading order in residents.php

## Success Criteria

All features should:
- ✓ Work without errors
- ✓ Provide visual feedback
- ✓ Be responsive
- ✓ Have smooth animations
- ✓ Be accessible
- ✓ Work on mobile devices

## Next Steps After Testing

If all tests pass:
1. ✓ Table is production-ready
2. ✓ Can be used as template for other tables
3. ✓ Document any customizations needed

If issues found:
1. Check browser console for errors
2. Verify all files are loaded correctly
3. Check script loading order
4. Review TABLE-ENHANCEMENTS.md for troubleshooting

---

**Testing Date:** _____________
**Tested By:** _____________
**Browser:** _____________
**Result:** ☐ Pass ☐ Fail
**Notes:** _____________
