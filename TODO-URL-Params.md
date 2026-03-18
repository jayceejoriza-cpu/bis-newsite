# TODO: Add URL Params for Requests Filtering (Feedback)

## Updated Plan (Server-side + JS Sync)
**No ResidentID/Name filters added per user.**

1. ✅ Update requests.php: Add $_GET parsing + dynamic SQL WHERE for certificate, purpose, date_requested
2. ✅ Update requests.php: Dynamic COUNT for totalRequests
3. ✅ Update requests.js: applyAdvancedFilters() → build/set URL params (reloads for server filter)
4. ✅ Update requests.js: Add loadFiltersFromUrl() on DOMContentLoaded (set inputs from URL)
5. ✅ Update clearAdvancedFilters(): Clear URL params
6. ✅ Final fix: Close filter modal on apply
7. ✅ Complete

## Status
Implementing URL param persistence with server-side filtering.

