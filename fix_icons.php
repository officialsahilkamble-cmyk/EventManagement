<?php
// Fix category icons
define('APP_ACCESS', true);
require_once 'common/config.php';

try {
    // Update existing categories with proper Font Awesome icons
    $updates = [
        'art-design' => 'fas fa-palette',
        'fashion' => 'fas fa-tshirt',
        'food-culinary' => 'fas fa-utensils',
        'music' => 'fas fa-music',
        'sports' => 'fas fa-futbol',
        'technology' => 'fas fa-laptop-code'
    ];

    $stmt = $pdo->prepare("UPDATE categories SET icon = ? WHERE slug = ?");

    foreach ($updates as $slug => $icon) {
        $stmt->execute([$icon, $slug]);
        echo "Updated $slug with icon $icon\n";
    }

    echo "\n✅ All category icons have been fixed!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
