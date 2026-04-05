<?php
// Background execution file - silently fails if not due, outputs nothing
require_once '../config.php';

$settingsFile = __DIR__ . '/backup_settings.json';
if (!file_exists($settingsFile)) {
    exit;
}

$settings = json_decode(file_get_contents($settingsFile), true);
if (!$settings || $settings['frequency'] === 'None') {
    exit;
}

$lastRun = isset($settings['last_run']) && !empty($settings['last_run']) ? strtotime($settings['last_run']) : 0;
$now = time();
$due = false;

$timeString = isset($settings['backup_time']) && !empty($settings['backup_time']) ? $settings['backup_time'] : '00:00';
$currentTimeStr = date('H:i');
$isPastTime = ($currentTimeStr >= $timeString);

if ($settings['frequency'] === 'Daily') {
    $targetToday = strtotime("today $timeString");
    $targetYesterday = strtotime("yesterday $timeString");
    
    if ($now >= $targetToday && $lastRun < $targetToday) {
        $due = true;
    } elseif ($now < $targetToday && $lastRun < $targetYesterday) {
        $due = true;
    }
} elseif ($settings['frequency'] === 'Weekly') {
    if ($isPastTime && ($now - $lastRun) >= 600000) { // ~6.9 days to prevent drift logic issues
        $due = true;
    }
} elseif ($settings['frequency'] === 'Monthly') {
    if ($isPastTime && ($now - $lastRun) >= 2500000) { // ~28.9 days
        $due = true;
    }
}

if ($due) {
    try {
        $conn->set_charset("utf8");

        $tables = array();
        $result = $conn->query('SHOW TABLES');
        while($row = $result->fetch_row()){
            $tables[] = $row[0];
        }

        $sqlScript = "-- Automated Database Backup: " . DB_NAME . "\n";
        $sqlScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach($tables as $table){
            $result = $conn->query('SHOW CREATE TABLE ' . $table);
            $row = $result->fetch_row();
            $sqlScript .= "\n\n" . $row[1] . ";\n\n";

            $result = $conn->query('SELECT * FROM ' . $table);
            $columnCount = $result->field_count;

            while($row = $result->fetch_row()){
                $sqlScript .= "INSERT INTO $table VALUES(";
                for($j=0; $j < $columnCount; $j++){
                    if (isset($row[$j])) {
                        $sqlScript .= '"' . $conn->real_escape_string($row[$j]) . '"';
                    } else {
                        $sqlScript .= 'NULL';
                    }
                    if ($j < ($columnCount - 1)) {
                        $sqlScript .= ',';
                    }
                }
                $sqlScript .= ");\n";
            }
            $sqlScript .= "\n";
        }
        $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;";

        // Prepare Zip
        $targetFolder = rtrim($settings['target_folder'], '/\\');
        if (!file_exists($targetFolder)) {
            @mkdir($targetFolder, 0755, true);
        }

        $zip = new ZipArchive();
        $zipFileName = $targetFolder . '/' . DB_NAME . '_autobackup_' . date('Y-m-d_H-i-s') . '.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString(DB_NAME . '_backup.sql', $sqlScript);
            
            if (!empty($settings['zip_password'])) {
                $zip->setEncryptionName(DB_NAME . '_backup.sql', ZipArchive::EM_AES_256, $settings['zip_password']);
            }
            
            $zip->close();

            // Update last run time to prevent repeated execution
            $settings['last_run'] = date('Y-m-d H:i:s');
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        }
    } catch (Exception $e) {
        error_log("Auto backup failed: " . $e->getMessage());
    }
}
?>