<?php
// Standalone migration - no config.php dependency
$conn = new mysqli('localhost', 'root', '', 'bmis');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . PHP_EOL);
}

$sqls = [
    "CREATE TABLE IF NOT EXISTS `roles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `color` VARCHAR(20) DEFAULT '#e5e7eb',
        `text_color` VARCHAR(20) DEFAULT '#374151',
        `permissions` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_role_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `user_roles` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) NOT NULL,
        `role_id` INT(11) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_role_id` (`role_id`),
        CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "INSERT IGNORE INTO `roles` (`name`, `description`, `color`, `text_color`) VALUES
        ('Administrator', 'Full system access and management',         '#fef3c7', '#92400e'),
        ('Staff',         'Regular staff member with standard access', '#dbeafe', '#1e40af'),
        ('Kagawad',       'Barangay Kagawad with official duties',     '#ede9fe', '#6d28d9'),
        ('Test',          'Test role for development purposes',        '#fee2e2', '#991b1b'),
        ('Viewer',        'Read-only access to the system',            '#f3f4f6', '#374151')",

    "INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
        SELECT 1, id FROM `roles` WHERE `name` = 'Administrator' LIMIT 1"
];

foreach ($sqls as $i => $sql) {
    if ($conn->query($sql)) {
        echo "OK [" . ($i+1) . "]: " . substr(trim(preg_replace('/\s+/', ' ', $sql)), 0, 70) . "..." . PHP_EOL;
    } else {
        echo "ERR [" . ($i+1) . "]: " . $conn->error . PHP_EOL;
    }
}

echo PHP_EOL . "Done. Tables: roles, user_roles" . PHP_EOL;
$conn->close();
?>
