<?php
/**
 * Batch Authentication Update Script
 * This script adds authentication checks to all PHP files that need protection
 */

$files_to_update = [
    // Main display pages
    'activity-logs.php',
    'create-resident.php',
    'create-certificate.php',
    'edit-resident.php',
    'edit-certificate.php',
    'edit_blotter.php',
    'resident_profile.php',
    
    // Pages with existing auth (to be replaced)
    'user-profile.php',
    'barangay-info.php',
    'backup.php',
    
    // Backend/API files
    'save_resident.php',
    'save_certificate.php',
    'save_household.php',
    'save_blotter_record.php',
    'save_barangay_info.php',
    'get_certificates.php',
    'get_resident_details.php',
    'get_household_details.php',
    'get_households.php',
    'search_residents.php',
    'generate_password.php',
    'get_certificate_preview.php',
    'save_certificate_request.php',
];

$updated = [];
$skipped = [];
$errors = [];

foreach ($files_to_update as $file) {
    if (!file_exists($file)) {
        $skipped[] = "$file (not found)";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if already has auth_check
    if (strpos($content, 'auth_check.php') !== false) {
        $skipped[] = "$file (already protected)";
        continue;
    }
    
    // Pattern 1: After require_once 'config.php';
    $pattern1 = '/(require_once\s+[\'"]config\.php[\'"];)/';
    if (preg_match($pattern1, $content)) {
        $newContent = preg_replace(
            $pattern1,
            "$1\n\n// Check authentication\nrequire_once 'auth_check.php';",
            $content,
            1
        );
        
        if (file_put_contents($file, $newContent)) {
            $updated[] = $file;
        } else {
            $errors[] = "$file (write failed)";
        }
        continue;
    }
    
    // Pattern 2: Replace existing auth check
    $pattern2 = '/if\s*\(\s*!isset\(\$_SESSION\[[\'"]user_id[\'"]\]\)\s*\)\s*\{[^}]*header\([^)]*login\.php[^)]*\);[^}]*exit\(\);[^}]*\}/s';
    if (preg_match($pattern2, $content)) {
        $newContent = preg_replace(
            $pattern2,
            "// Check authentication\nrequire_once 'auth_check.php';",
            $content,
            1
        );
        
        if (file_put_contents($file, $newContent)) {
            $updated[] = $file;
        } else {
            $errors[] = "$file (write failed)";
        }
        continue;
    }
    
    // Pattern 3: At the beginning after <?php
    if (strpos($content, '<?php') === 0) {
        $newContent = str_replace(
            '<?php',
            "<?php\n// Check authentication\nrequire_once 'auth_check.php';\n",
            $content
        );
        
        if (file_put_contents($file, $newContent)) {
            $updated[] = $file;
        } else {
            $errors[] = "$file (write failed)";
        }
        continue;
    }
    
    $skipped[] = "$file (no suitable pattern found)";
}

// Output results
echo "=== Batch Authentication Update Results ===\n\n";

echo "✅ UPDATED (" . count($updated) . " files):\n";
foreach ($updated as $file) {
    echo "  - $file\n";
}
echo "\n";

echo "⏭️  SKIPPED (" . count($skipped) . " files):\n";
foreach ($skipped as $file) {
    echo "  - $file\n";
}
echo "\n";

if (!empty($errors)) {
    echo "❌ ERRORS (" . count($errors) . " files):\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

echo "=== Summary ===\n";
echo "Total files processed: " . count($files_to_update) . "\n";
echo "Successfully updated: " . count($updated) . "\n";
echo "Skipped: " . count($skipped) . "\n";
echo "Errors: " . count($errors) . "\n";
?>
