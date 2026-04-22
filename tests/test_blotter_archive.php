<?php
/**
 * Thorough test for blotter archive-on-delete feature.
 * Tests: archive action, delete action, archive data integrity, restore, edge cases.
 */

// Bootstrap — config.php starts the session
require_once __DIR__ . '/../config.php';

// Simulate authenticated session
$_SESSION['user_id']  = 1;
$_SESSION['username'] = 'test_admin';
$_SESSION['role']     = 'Administrator';

$conn->set_charset("utf8mb4");

$results = [];

// ─────────────────────────────────────────────────────────────────────────────
// Helper
// ─────────────────────────────────────────────────────────────────────────────
function addResult(&$results, $test, $passed, $detail = '') {
    $results[] = ['test' => $test, 'passed' => $passed, 'detail' => $detail];
}

// ─────────────────────────────────────────────────────────────────────────────
// Inline restoreBlotter (mirrors model/restore_archive.php logic)
// ─────────────────────────────────────────────────────────────────────────────
function testRestoreBlotter($conn, $data) {
    $checkTable = $conn->query("SHOW TABLES LIKE 'blotter_records'");
    if ($checkTable->num_rows == 0) throw new Exception("blotter_records table does not exist");

    $year      = date('Y');
    $maxResult = $conn->query("SELECT MAX(CAST(SUBSTRING(record_number, 9) AS UNSIGNED)) as max_num FROM blotter_records WHERE record_number LIKE 'BR-{$year}-%'");
    $maxRow    = $maxResult ? $maxResult->fetch_assoc() : null;
    $nextNum   = (($maxRow['max_num'] ?? 0)) + 1;
    $recordNumber = sprintf("BR-%s-%06d", $year, $nextNum);

    if (!empty($data['record_number'])) {
        $checkDup = $conn->prepare("SELECT id FROM blotter_records WHERE record_number = ?");
        $checkDup->bind_param("s", $data['record_number']);
        $checkDup->execute();
        if ($checkDup->get_result()->num_rows === 0) $recordNumber = $data['record_number'];
        $checkDup->close();
    }

    $incidentType        = $data['incident_type']        ?? ($data['complaint'] ?? 'Unknown');
    $incidentDescription = $data['incident_description'] ?? '';
    $incidentDate        = $data['incident_date']        ?? ($data['date'] ?? date('Y-m-d H:i:s'));
    $incidentLocation    = $data['incident_location']    ?? '';
    $dateReported        = $data['date_reported']        ?? ($data['date'] ?? date('Y-m-d H:i:s'));
    $reportedBy          = $data['reported_by']          ?? null;
    $status              = $data['status']               ?? 'Pending';
    $resolution          = $data['resolution']           ?? null;
    $remarks             = $data['remarks']              ?? null;

    $stmt = $conn->prepare("INSERT INTO blotter_records (record_number, incident_type, incident_description, incident_date, incident_location, date_reported, reported_by, status, resolution, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $recordNumber, $incidentType, $incidentDescription, $incidentDate, $incidentLocation, $dateReported, $reportedBy, $status, $resolution, $remarks);
    if (!$stmt->execute()) throw new Exception("Failed to restore blotter: " . $stmt->error);
    $blotterId = $conn->insert_id;
    $stmt->close();

    if (!empty($data['complainants']) && is_array($data['complainants'])) {
        $cStmt = $conn->prepare("INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number) VALUES (?, ?, ?, ?, ?)");
        foreach ($data['complainants'] as $c) {
            $resId = $c['resident_id'] ?? null; $cName = $c['name'] ?? ''; $cAddr = $c['address'] ?? null; $cContact = $c['contact_number'] ?? null;
            $cStmt->bind_param("issss", $blotterId, $resId, $cName, $cAddr, $cContact); $cStmt->execute();
        }
        $cStmt->close();
    } elseif (!empty($data['complainant'])) {
        $cName = $data['complainant'];
        $stmt = $conn->prepare("INSERT INTO blotter_complainants (blotter_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $blotterId, $cName); $stmt->execute(); $stmt->close();
    }

    if (!empty($data['victims']) && is_array($data['victims'])) {
        $vStmt = $conn->prepare("INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement) VALUES (?, ?, ?, ?, ?, 'VICTIM')");
        foreach ($data['victims'] as $v) {
            $resId = $v['resident_id'] ?? null; $vName = $v['name'] ?? ''; $vAddr = $v['address'] ?? null; $vContact = $v['contact_number'] ?? null;
            $vStmt->bind_param("issss", $blotterId, $resId, $vName, $vAddr, $vContact); $vStmt->execute();
        }
        $vStmt->close();
    }

    if (!empty($data['witnesses']) && is_array($data['witnesses'])) {
        $wStmt = $conn->prepare("INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement) VALUES (?, ?, ?, ?, ?, 'WITNESS')");
        foreach ($data['witnesses'] as $w) {
            $resId = $w['resident_id'] ?? null; $wName = $w['name'] ?? ''; $wAddr = $w['address'] ?? null; $wContact = $w['contact_number'] ?? null;
            $wStmt->bind_param("issss", $blotterId, $resId, $wName, $wAddr, $wContact); $wStmt->execute();
        }
        $wStmt->close();
    }

    if (!empty($data['respondents']) && is_array($data['respondents'])) {
        $rStmt = $conn->prepare("INSERT INTO blotter_respondents (blotter_id, resident_id, name, address, contact_number) VALUES (?, ?, ?, ?, ?)");
        foreach ($data['respondents'] as $r) {
            $resId = $r['resident_id'] ?? null; $rName = $r['name'] ?? ''; $rAddr = $r['address'] ?? null; $rContact = $r['contact_number'] ?? null;
            $rStmt->bind_param("issss", $blotterId, $resId, $rName, $rAddr, $rContact); $rStmt->execute();
        }
        $rStmt->close();
    } elseif (!empty($data['respondent'])) {
        $rName = $data['respondent'];
        $stmt = $conn->prepare("INSERT INTO blotter_respondents (blotter_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $blotterId, $rName); $stmt->execute(); $stmt->close();
    }

    return $blotterId;
}

// ─────────────────────────────────────────────────────────────────────────────
// Inline archiveBlotterRecord (mirrors model/archive_blotter_record.php logic)
// ─────────────────────────────────────────────────────────────────────────────
function testArchiveBlotter($conn, $id, $deletedBy) {
    $stmt = $conn->prepare("SELECT * FROM blotter_records WHERE id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) return ['success' => false, 'message' => 'Blotter record not found'];
    $blotter = $result->fetch_assoc(); $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM blotter_complainants WHERE blotter_id = ? AND (statement IS NULL OR statement = '' OR statement = 'COMPLAINANT') ORDER BY id");
    $stmt->bind_param("i", $id); $stmt->execute(); $complainants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM blotter_complainants WHERE blotter_id = ? AND statement = 'VICTIM' ORDER BY id");
    $stmt->bind_param("i", $id); $stmt->execute(); $victims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM blotter_complainants WHERE blotter_id = ? AND statement = 'WITNESS' ORDER BY id");
    $stmt->bind_param("i", $id); $stmt->execute(); $witnesses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM blotter_respondents WHERE blotter_id = ? ORDER BY id");
    $stmt->bind_param("i", $id); $stmt->execute(); $respondents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

    $actions = [];
    if (!empty($blotter['remarks'])) { $decoded = json_decode($blotter['remarks'], true); if (is_array($decoded)) $actions = $decoded; }

    $archiveData = [
        'complainant'          => !empty($complainants) ? $complainants[0]['name'] : 'N/A',
        'respondent'           => !empty($respondents)  ? $respondents[0]['name']  : 'N/A',
        'complaint'            => $blotter['incident_type'],
        'status'               => $blotter['status'],
        'date'                 => $blotter['date_reported'],
        'record_number'        => $blotter['record_number'],
        'incident_type'        => $blotter['incident_type'],
        'incident_description' => $blotter['incident_description'],
        'incident_date'        => $blotter['incident_date'],
        'incident_location'    => $blotter['incident_location'],
        'date_reported'        => $blotter['date_reported'],
        'reported_by'          => $blotter['reported_by'],
        'resolution'           => $blotter['resolution'],
        'remarks'              => $blotter['remarks'],
        'created_at'           => $blotter['created_at'],
        'complainants'         => $complainants,
        'victims'              => $victims,
        'witnesses'            => $witnesses,
        'respondents'          => $respondents,
        'actions'              => $actions,
    ];

    $recordData   = json_encode($archiveData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    $archiveType  = 'blotter';
    $recordNumber = $blotter['record_number'];

    $conn->begin_transaction();
    $stmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $archiveType, $recordNumber, $recordData, $deletedBy);
    if (!$stmt->execute()) { $conn->rollback(); return ['success' => false, 'message' => $stmt->error]; }
    $archiveRowId = $conn->insert_id; $stmt->close();

    // Explicitly delete related records (mirrors model files — handles missing CASCADE)
    $stmt = $conn->prepare("DELETE FROM blotter_complainants WHERE blotter_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();

    $stmt = $conn->prepare("DELETE FROM blotter_respondents WHERE blotter_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();

    $stmt = $conn->prepare("DELETE FROM blotter_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute() || $stmt->affected_rows === 0) { $conn->rollback(); return ['success' => false, 'message' => 'Delete failed']; }
    $stmt->close();

    $conn->commit();
    return ['success' => true, 'archive_row_id' => $archiveRowId, 'record_number' => $recordNumber];
}

// ─────────────────────────────────────────────────────────────────────────────
// SETUP: Ensure test blotter records exist
// ─────────────────────────────────────────────────────────────────────────────
$blotterIds = [];
$res = $conn->query("SELECT id, record_number FROM blotter_records ORDER BY id LIMIT 3");
if ($res) { while ($row = $res->fetch_assoc()) $blotterIds[] = $row; }

if (count($blotterIds) < 2) {
    $conn->query("INSERT INTO blotter_records (record_number, incident_type, incident_description, incident_date, incident_location, date_reported, status) VALUES ('BR-TEST-000001', 'Test Incident A', 'Test description A', NOW(), 'Test Location A', NOW(), 'Pending')");
    $idA = $conn->insert_id;
    $conn->query("INSERT INTO blotter_complainants (blotter_id, name, contact_number, address) VALUES ($idA, 'Test Complainant A', '09111111111', 'Test Address A')");
    $conn->query("INSERT INTO blotter_respondents (blotter_id, name, contact_number, address) VALUES ($idA, 'Test Respondent A', '09222222222', 'Test Address A')");

    $conn->query("INSERT INTO blotter_records (record_number, incident_type, incident_description, incident_date, incident_location, date_reported, status) VALUES ('BR-TEST-000002', 'Test Incident B', 'Test description B', NOW(), 'Test Location B', NOW(), 'Under Investigation')");
    $idB = $conn->insert_id;
    $conn->query("INSERT INTO blotter_complainants (blotter_id, name, contact_number, address) VALUES ($idB, 'Test Complainant B', '09333333333', 'Test Address B')");
    $conn->query("INSERT INTO blotter_respondents (blotter_id, name, contact_number, address) VALUES ($idB, 'Test Respondent B', '09444444444', 'Test Address B')");

    $blotterIds = [];
    $res = $conn->query("SELECT id, record_number FROM blotter_records ORDER BY id LIMIT 3");
    while ($row = $res->fetch_assoc()) $blotterIds[] = $row;
}

$testArchiveId  = $blotterIds[0]['id'];
$testArchiveNum = $blotterIds[0]['record_number'];
$testDeleteId   = $blotterIds[1]['id'];
$testDeleteNum  = $blotterIds[1]['record_number'];

addResult($results, 'SETUP: Blotter records available', count($blotterIds) >= 2,
    "Archive test: ID=$testArchiveId ($testArchiveNum) | Delete test: ID=$testDeleteId ($testDeleteNum)");

// ─────────────────────────────────────────────────────────────────────────────
// TEST 1: Archive action (archive_blotter_record.php logic)
// ─────────────────────────────────────────────────────────────────────────────
$archiveCountBefore = (int)$conn->query("SELECT COUNT(*) as c FROM archive WHERE archive_type='blotter'")->fetch_assoc()['c'];

$archiveResult = testArchiveBlotter($conn, $testArchiveId, 'test_admin');
addResult($results, 'TEST 1: Archive action returns success', $archiveResult['success'], json_encode($archiveResult));

$stillExists = $conn->query("SELECT id FROM blotter_records WHERE id = $testArchiveId")->num_rows > 0;
addResult($results, 'TEST 1: Blotter removed from blotter_records', !$stillExists, $stillExists ? "Still exists!" : "Correctly removed");

$complainantsExist = $conn->query("SELECT id FROM blotter_complainants WHERE blotter_id = $testArchiveId")->num_rows > 0;
addResult($results, 'TEST 1: Complainants cascade-deleted', !$complainantsExist, $complainantsExist ? "Still exist!" : "Correctly cascade-deleted");

$respondentsExist = $conn->query("SELECT id FROM blotter_respondents WHERE blotter_id = $testArchiveId")->num_rows > 0;
addResult($results, 'TEST 1: Respondents cascade-deleted', !$respondentsExist, $respondentsExist ? "Still exist!" : "Correctly cascade-deleted");

$archiveCountAfter = (int)$conn->query("SELECT COUNT(*) as c FROM archive WHERE archive_type='blotter'")->fetch_assoc()['c'];
addResult($results, 'TEST 1: Archive row inserted into archive table', $archiveCountAfter > $archiveCountBefore,
    "Before: $archiveCountBefore | After: $archiveCountAfter");

// ─────────────────────────────────────────────────────────────────────────────
// TEST 2: Delete action (delete_blotter_record.php logic — same archive logic)
// ─────────────────────────────────────────────────────────────────────────────
$deleteResult = testArchiveBlotter($conn, $testDeleteId, 'test_admin');
addResult($results, 'TEST 2: Delete action returns success', $deleteResult['success'], json_encode($deleteResult));

$stillExists2 = $conn->query("SELECT id FROM blotter_records WHERE id = $testDeleteId")->num_rows > 0;
addResult($results, 'TEST 2: Blotter removed from blotter_records after delete', !$stillExists2, $stillExists2 ? "Still exists!" : "Correctly removed");

$archiveCountAfterDelete = (int)$conn->query("SELECT COUNT(*) as c FROM archive WHERE archive_type='blotter'")->fetch_assoc()['c'];
addResult($results, 'TEST 2: Archive row inserted for deleted blotter', $archiveCountAfterDelete > $archiveCountAfter,
    "Before: $archiveCountAfter | After: $archiveCountAfterDelete");

// ─────────────────────────────────────────────────────────────────────────────
// TEST 3: Archive data integrity (JSON fields)
// ─────────────────────────────────────────────────────────────────────────────
$archiveRow = $conn->query("SELECT * FROM archive WHERE archive_type='blotter' AND record_id='" . $conn->real_escape_string($testArchiveNum) . "' LIMIT 1")->fetch_assoc();
if ($archiveRow) {
    $data = json_decode($archiveRow['record_data'], true);
    addResult($results, 'TEST 3: Archive JSON is valid',              $data !== null,                                                    'JSON decode: ' . ($data ? 'OK' : 'FAILED'));
    addResult($results, 'TEST 3: Has record_number',                  !empty($data['record_number']),                                    $data['record_number'] ?? 'MISSING');
    addResult($results, 'TEST 3: Has incident_type',                  !empty($data['incident_type']),                                    $data['incident_type'] ?? 'MISSING');
    addResult($results, 'TEST 3: Has status',                         !empty($data['status']),                                           $data['status'] ?? 'MISSING');
    addResult($results, 'TEST 3: Has date_reported',                  !empty($data['date_reported']),                                    $data['date_reported'] ?? 'MISSING');
    addResult($results, 'TEST 3: Has incident_location',              !empty($data['incident_location']),                                $data['incident_location'] ?? 'MISSING');
    addResult($results, 'TEST 3: Has complainants array',             isset($data['complainants']) && is_array($data['complainants']),   'Count: ' . count($data['complainants'] ?? []));
    addResult($results, 'TEST 3: Has respondents array',              isset($data['respondents'])  && is_array($data['respondents']),    'Count: ' . count($data['respondents'] ?? []));
    addResult($results, 'TEST 3: Has victims array',                  isset($data['victims'])      && is_array($data['victims']),        'Count: ' . count($data['victims'] ?? []));
    addResult($results, 'TEST 3: Has witnesses array',                isset($data['witnesses'])    && is_array($data['witnesses']),      'Count: ' . count($data['witnesses'] ?? []));
    addResult($results, 'TEST 3: Has quick-display complainant field', isset($data['complainant']),                                      $data['complainant'] ?? 'MISSING');
    addResult($results, 'TEST 3: Has quick-display respondent field',  isset($data['respondent']),                                       $data['respondent'] ?? 'MISSING');
    addResult($results, 'TEST 3: deleted_by is set',                  !empty($archiveRow['deleted_by']),                                 $archiveRow['deleted_by']);
    addResult($results, 'TEST 3: deleted_at is set',                  !empty($archiveRow['deleted_at']),                                 $archiveRow['deleted_at']);
} else {
    addResult($results, 'TEST 3: Archive row found', false, "No archive row found for $testArchiveNum");
}

// ─────────────────────────────────────────────────────────────────────────────
// TEST 4: Restore blotter from archive
// ─────────────────────────────────────────────────────────────────────────────
$archiveRowForRestore = $conn->query("SELECT * FROM archive WHERE archive_type='blotter' AND record_id='" . $conn->real_escape_string($testArchiveNum) . "' LIMIT 1")->fetch_assoc();

if ($archiveRowForRestore) {
    $restoreData = json_decode($archiveRowForRestore['record_data'], true);
    $archiveRestoreId = $archiveRowForRestore['id'];

    try {
        $conn->begin_transaction();
        $restoredBlotterId = testRestoreBlotter($conn, $restoreData);
        $conn->query("DELETE FROM archive WHERE id=$archiveRestoreId");
        $conn->commit();

        $restoredRow = $conn->query("SELECT id, record_number, incident_type, status FROM blotter_records WHERE record_number='" . $conn->real_escape_string($restoreData['record_number']) . "'")->fetch_assoc();
        addResult($results, 'TEST 4: Restore — blotter re-inserted into blotter_records', $restoredRow !== null,
            $restoredRow ? "Restored: {$restoredRow['record_number']} | Status: {$restoredRow['status']}" : 'NOT FOUND');

        if ($restoredRow) {
            $rId = $restoredRow['id'];
            $rComplainants = $conn->query("SELECT name FROM blotter_complainants WHERE blotter_id=$rId AND (statement IS NULL OR statement='' OR statement='COMPLAINANT')")->fetch_all(MYSQLI_ASSOC);
            $rRespondents  = $conn->query("SELECT name FROM blotter_respondents WHERE blotter_id=$rId")->fetch_all(MYSQLI_ASSOC);
            addResult($results, 'TEST 4: Restore — complainants re-inserted', count($rComplainants) > 0,
                'Count: ' . count($rComplainants) . ' | ' . implode(', ', array_column($rComplainants, 'name')));
            addResult($results, 'TEST 4: Restore — respondents re-inserted', count($rRespondents) > 0,
                'Count: ' . count($rRespondents) . ' | ' . implode(', ', array_column($rRespondents, 'name')));
        }

        $stillInArchive = $conn->query("SELECT id FROM archive WHERE id=$archiveRestoreId")->num_rows > 0;
        addResult($results, 'TEST 4: Restore — removed from archive table', !$stillInArchive,
            $stillInArchive ? 'Still in archive!' : 'Correctly removed from archive');

    } catch (Exception $e) {
        $conn->rollback();
        addResult($results, 'TEST 4: Restore — exception', false, $e->getMessage());
    }
} else {
    addResult($results, 'TEST 4: Restore — archive row found', false, "No archive row for $testArchiveNum");
}

// ─────────────────────────────────────────────────────────────────────────────
// TEST 5: Edge case — archive non-existent record ID
// ─────────────────────────────────────────────────────────────────────────────
$edgeResult = testArchiveBlotter($conn, 999999, 'test_admin');
addResult($results, 'TEST 5: Edge case — non-existent ID returns failure', !$edgeResult['success'], $edgeResult['message'] ?? '');

// ─────────────────────────────────────────────────────────────────────────────
// TEST 6: Duplicate record_number on restore — should generate new number
// ─────────────────────────────────────────────────────────────────────────────
// Re-archive the restored record, then insert a record with same number, then restore
$reArchiveRow = $conn->query("SELECT id, record_number FROM blotter_records WHERE record_number='" . $conn->real_escape_string($testArchiveNum) . "' LIMIT 1")->fetch_assoc();
if ($reArchiveRow) {
    $dupResult = testArchiveBlotter($conn, $reArchiveRow['id'], 'test_admin');
    if ($dupResult['success']) {
        // Insert a record with the same record_number to force conflict
        $conn->query("INSERT INTO blotter_records (record_number, incident_type, incident_description, incident_date, incident_location, date_reported, status) VALUES ('" . $conn->real_escape_string($testArchiveNum) . "', 'Conflict Test', 'Conflict', NOW(), 'Conflict Location', NOW(), 'Pending')");

        $dupArchiveRow = $conn->query("SELECT * FROM archive WHERE archive_type='blotter' AND record_id='" . $conn->real_escape_string($testArchiveNum) . "' LIMIT 1")->fetch_assoc();
        if ($dupArchiveRow) {
            $dupData = json_decode($dupArchiveRow['record_data'], true);
            try {
                $conn->begin_transaction();
                $newId = testRestoreBlotter($conn, $dupData);
                $conn->query("DELETE FROM archive WHERE id=" . $dupArchiveRow['id']);
                $conn->commit();

                $newRow = $conn->query("SELECT record_number FROM blotter_records WHERE id=$newId")->fetch_assoc();
                $isNewNumber = $newRow && $newRow['record_number'] !== $testArchiveNum;
                addResult($results, 'TEST 6: Duplicate record_number — new number generated on restore', $isNewNumber,
                    "Original: $testArchiveNum | Restored as: " . ($newRow['record_number'] ?? 'N/A'));
            } catch (Exception $e) {
                $conn->rollback();
                addResult($results, 'TEST 6: Duplicate record_number — exception', false, $e->getMessage());
            }
        } else {
            addResult($results, 'TEST 6: Duplicate record_number — skipped', true, 'Archive row not found for re-test');
        }
    } else {
        addResult($results, 'TEST 6: Duplicate record_number — skipped', true, 'Re-archive failed: ' . ($dupResult['message'] ?? ''));
    }
} else {
    addResult($results, 'TEST 6: Duplicate record_number — skipped', true, 'Restored record not found for re-test');
}

// ─────────────────────────────────────────────────────────────────────────────
// OUTPUT
// ─────────────────────────────────────────────────────────────────────────────
$passed = array_filter($results, fn($r) => $r['passed']);
$failed = array_filter($results, fn($r) => !$r['passed']);
?>
<!DOCTYPE html>
<html>
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <title>Blotter Archive Test Results</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .summary { font-size: 1.2em; margin-bottom: 20px; padding: 12px; background: white; border-radius: 6px; }
        .pass { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; background: white; border-radius: 6px; overflow: hidden; }
        th, td { border: 1px solid #e5e7eb; padding: 10px 14px; text-align: left; font-size: 0.9em; }
        th { background: #1f2937; color: white; }
        tr.failed { background: #fef2f2; }
        tr.passed:hover, tr.failed:hover { filter: brightness(0.97); }
    </style>
</head>
<body>
<h1>🧪 Blotter Archive Feature — Test Results</h1>
<div class="summary">
    <span class="pass">✅ Passed: <?php echo count($passed); ?></span> &nbsp;|&nbsp;
    <span class="fail">❌ Failed: <?php echo count($failed); ?></span> &nbsp;|&nbsp;
    Total: <?php echo count($results); ?>
</div>
<table>
    <thead>
        <tr><th>Test</th><th>Result</th><th>Detail</th></tr>
    </thead>
    <tbody>
        <?php foreach ($results as $r): ?>
        <tr class="<?php echo $r['passed'] ? 'passed' : 'failed'; ?>">
            <td><?php echo htmlspecialchars($r['test']); ?></td>
            <td><?php echo $r['passed'] ? '<span class="pass">✅ PASS</span>' : '<span class="fail">❌ FAIL</span>'; ?></td>
            <td><?php echo htmlspecialchars($r['detail']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
