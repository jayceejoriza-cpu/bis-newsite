# Official User Page - TODO

## Steps

- [x] Plan approved by user
- [x] Create `official-user.php` — main page with users table + Create User modal
- [x] Create `assets/css/official-user.css` — page-specific styles
- [x] Create `assets/js/official-user.js` — search, modal, AJAX, refresh, role badge toggle
- [x] Create `model/save-user.php` — backend: validate, hash password, insert/edit/delete user
- [x] Update `components/sidebar.php` — Users submenu with `official-user.php` + permission-based visibility
- [x] Run DB migration — `users.role` changed from ENUM to VARCHAR(100)
- [x] Create `permissions.php` — loads user permissions from assigned roles; Admin bypasses all
- [x] Update `official-user.php` — enforce `perm_office_view`; hide Create/Edit/Delete per permission
- [x] Update `roles.php` — enforce `perm_roles_view`; hide Create/Edit/Delete per permission
- [x] Add `perm_office_delete` to roles.php modal
- [x] PHP syntax validation passed on all files
- [x] Update `model/save-user.php` — handle user_roles assignments (syncUserRoles)
- [x] Update `roles.php` — fetch roles from DB, action menu, delete confirm modal
- [x] Update `assets/js/roles.js` — save/edit/delete via AJAX to save-role.php
- [x] Update `assets/css/roles.css` — action menu, delete modal, toast styles added
- [x] Update `assets/css/official-user.css` — role badge list + checkbox styles added
- [x] Update `assets/js/official-user.js` — role checkboxes, pre-check on edit
