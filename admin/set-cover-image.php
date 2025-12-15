<?php
/**
 * Set Cover Image API
 */

define('APP_ACCESS', true);
require_once '../common/config.php';
require_once '../common/functions.php';
require_once '../common/auth.php';

// Set JSON header first
header('Content-Type: application/json');

// Check admin auth for AJAX
if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $image_id = $input['image_id'] ?? 0;
    $type = $input['type'] ?? '';
    $entity_id = $input['entity_id'] ?? 0;

    if ($type !== 'event') {
        throw new Exception('Cover images are only supported for events');
    }

    // Unset all cover images for this event
    $stmt = $pdo->prepare("UPDATE event_gallery SET is_cover = 0 WHERE event_id = ?");
    $stmt->execute([$entity_id]);

    // Set the new cover image
    $stmt = $pdo->prepare("UPDATE event_gallery SET is_cover = 1 WHERE id = ? AND event_id = ?");
    $stmt->execute([$image_id, $entity_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Image not found or does not belong to this event');
    }

    $response['success'] = true;
    $response['message'] = 'Cover image updated successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
