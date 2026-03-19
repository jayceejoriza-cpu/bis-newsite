<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$residentId = intval($_POST['resident_id'] ?? 0);
$householdHeadValue = $_POST['householdHeadValue'] ?? '';

if ($residentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resident']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($householdHeadValue === 'Yes') {
        $householdNumber = $conn->real_escape_string(trim($_POST['householdNumber'] ?? ''));
        $householdContact = $conn->real_escape_string(trim($_POST['householdContact'] ?? ''));
        $householdAddress = $conn->real_escape_string(trim($_POST['householdAddress'] ?? ''));
        $waterSourceType = $conn->real_escape_string(trim($_POST['waterSourceType'] ?? ''));
        $toiletFacilityType = $conn->real_escape_string(trim($_POST['toiletFacilityType'] ?? ''));

        if (empty($householdNumber)) {
            throw new Exception("Household number is required.");
        }

        // Check if household number already exists
        $check = $conn->query("SELECT id FROM households WHERE household_number = '$householdNumber'");
        if ($check->num_rows > 0) {
            throw new Exception("Household number '$householdNumber' already exists.");
        }

        $sql = "INSERT INTO households (household_number, household_head_id, household_contact, address, water_source_type, toilet_facility_type, created_at)
                VALUES ('$householdNumber', $residentId, " . ($householdContact ? "'$householdContact'" : "NULL") . ", " . ($householdAddress ? "'$householdAddress'" : "''") . ", " . ($waterSourceType ? "'$waterSourceType'" : "NULL") . ", " . ($toiletFacilityType ? "'$toiletFacilityType'" : "NULL") . ", NOW())";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to create household: " . $conn->error);
        }
    } elseif ($householdHeadValue === 'No') {
        $selectedHouseholdId = intval($_POST['selectedHouseholdId'] ?? 0);
        $relationship = $conn->real_escape_string(trim($_POST['householdRelationship'] ?? ''));

        if ($selectedHouseholdId <= 0) {
            throw new Exception("Please select a household.");
        }
        if (empty($relationship)) {
            throw new Exception("Please specify your relationship to the household head.");
        }

        // Check if already in this household
        $check = $conn->query("SELECT id FROM household_members WHERE household_id = $selectedHouseholdId AND resident_id = $residentId");
        if ($check->num_rows > 0) {
            throw new Exception("Resident is already a member of this household.");
        }

        $sql = "INSERT INTO household_members (household_id, resident_id, relationship_to_head, is_head)
                VALUES ($selectedHouseholdId, $residentId, '$relationship', 0)";
        
        if (!$conn->query($sql)) {
            throw new Exception("Failed to add resident to household: " . $conn->error);
        }
    } else {
        throw new Exception("Please specify if the resident is a household head.");
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Successfully added to household.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
