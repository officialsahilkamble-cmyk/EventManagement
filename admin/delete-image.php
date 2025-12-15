<?php
/**
 * Delete Image API
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

    if (!in_array($type, ['event', 'venue'])) {
        throw new Exception('Invalid type');
    }

    if ($type === 'event') {
        // Get image path
        $stmt = $pdo->prepare("SELECT file_path FROM event_gallery WHERE id = ?");
        $stmt->execute([$image_id]);
        $image = $stmt->fetch();

        if (!$image) {
            throw new Exception('Image not found');
        }

        // Delete file
        $file_path = UPLOAD_PATH . $image['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM event_gallery WHERE id = ?");
        $stmt->execute([$image_id]);

    } else {
        // For venues, just clear the image_path
        $stmt = $pdo->prepare("SELECT image_path FROM venues WHERE id = ?");
        $stmt->execute([$image_id]);
        $venue = $stmt->fetch();

        if ($venue && $venue['image_path']) {
            $file_path = UPLOAD_PATH . $venue['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $stmt = $pdo->prepare("UPDATE venues SET image_path = NULL WHERE id = ?");
            $stmt->execute([$image_id]);
        }
    }

    $response['success'] = true;
    $response['message'] = 'Image deleted successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
