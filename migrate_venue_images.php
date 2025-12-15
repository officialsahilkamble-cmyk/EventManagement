<?php
// Add image_path column to venues table
define('APP_ACCESS', true);
require_once 'common/config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM venues LIKE 'image_path'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE venues ADD COLUMN image_path VARCHAR(255) NULL AFTER contact_phone");
        echo "✅ Added image_path column to venues table\n";
    } else {
        echo "ℹ️  image_path column already exists in venues table\n";
    }

    echo "\n✅ Database migration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
