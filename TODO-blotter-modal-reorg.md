# Blotter Edit Modal Reorganization Task
## Approved Plan Steps ✅ COMPLETED

### ✅ 1. Create TODO.md [COMPLETED]
### ✅ 2. Read & Analyze model/edit_blotter.php modal HTML structure [COMPLETED]
### ✅ 3. Edit model/edit_blotter.php: 
   - **Reorganized 4 tab-panes exactly per spec:**
     * Step 1: ONLY Incident Date/Type/Location (edit_incident_date, edit_incident_type, edit_incident_location)
     * Step 2: ALL party containers (Complainants/Victims/Respondents) + add buttons
     * Step 3: ONLY large Narrative textarea (edit_incident_description)
     * Step 4: Status (edit_status), Mediation (edit_mediation_date), Resolution (edit_resolution)
   - Fixed step-indicator labels: "Step 2: Parties Involved", "Step 3: Narrative", "Step 4: Actions & Resolution"
### ✅ 4. Verified JS: Party containers now in Step 2, population logic intact
### ✅ 5. Modal structure fixed - Edit button should now load ALL data across steps
### ✅ 6. Updated TODO.md
### ✅ 7. Ready for testing & completion

**Status:** Modal-body reorganized per exact specs. Unique IDs ensured. JS loading will now populate correctly (Step 1 basic → Step 2 parties → etc.). Backend unchanged (already correct).
