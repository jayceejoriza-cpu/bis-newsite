# Pagination Fix - TODO

## Steps

- [x] Read and analyze `residents.php`, `assets/js/residents.js`, `assets/js/table.js`, `assets/css/residents.css`
- [x] Identify root causes of broken pagination
- [x] Confirm plan with user
- [x] Fix `assets/js/table.js` — add `this.updateDisplay()` in `init()` after `this.updatePagination()`
- [x] Fix `residents.php` — repair broken `</button>` orphan tag in pagination HTML
- [x] Verify fixes
