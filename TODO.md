# Requests PHP Conversion Complete

**Original Task**: Convert JS `loadRequests()` to PHP in `requests.php`.

**Status**: ✅ Complete

**Changes Made**:
- `requests.php`: Added server-side PDO query/render for `#requestsTableBody` (data, no-data, error states matching JS exactly).
- `assets/js/requests.js`: Removed `loadRequests()` function and call; added direct EnhancedTable init in DOMContentLoaded; updated refresh to `location.reload()`.

**Result**:
- Table loads instantly with PHP data.
- All client JS features (sort, search, paginate, filter) work on server rows.
- Refresh reloads fresh data.

**Test**: `requests.php` now uses PHP-only for data loading.

No further steps needed.
