# Blotter Archive on Delete - TODO

## Steps

- [x] Gather information and plan
- [x] Create `model/delete_blotter_record.php` - Archives blotter then deletes from active table
- [x] Create `model/archive_blotter_record.php` - Archives blotter then deletes from active table
- [x] Fix `model/restore_archive.php` - Update `restoreBlotter()` to use new `blotter_records` schema
- [x] Update `archive.php` - Show richer blotter data in `viewDetails()` JS function
