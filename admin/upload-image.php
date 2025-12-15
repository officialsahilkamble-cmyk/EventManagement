<?php
/**
 * Image Upload Handler for Events and Venues
 * Supports multiple image uploads with cover image selection
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

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $type = $_POST['type'] ?? ''; // 'event' or 'venue'
    $entity_id = $_POST['entity_id'] ?? 0;
    $is_cover = isset($_POST['is_cover']) ? 1 : 0;

    if (!in_array($type, ['event', 'venue'])) {
        throw new Exception('Invalid upload type');
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    // Validate file
    $file = $_FILES['image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and WebP are allowed.');
    }

    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 5MB limit');
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;

    // Determine upload directory
    $upload_dir = UPLOAD_PATH . ($type === 'event' ? 'events/' : 'venues/');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $target_path = $upload_dir . $filename;
    $db_path = ($type === 'event' ? 'events/' : 'venues/') . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Insert into database
    if ($type === 'event') {
        // If this is a cover image, unset other cover images for this event
        if ($is_cover) {
            $stmt = $pdo->prepare("UPDATE event_gallery SET is_cover = 0 WHERE event_id = ?");
            $stmt->execute([$entity_id]);
        }

        $stmt = $pdo->prepare("INSERT INTO event_gallery (event_id, file_path, is_cover) VALUES (?, ?, ?)");
        $stmt->execute([$entity_id, $db_path, $is_cover]);
        $image_id = $pdo->lastInsertId();
    } else {
        // For venues, update the venue record with image path
        $stmt = $pdo->prepare("UPDATE venues SET image_path = ? WHERE id = ?");
        $stmt->execute([$db_path, $entity_id]);
        $image_id = $entity_id;
    }

    $response['success'] = true;
    $response['message'] = 'Image uploaded successfully';
    $response['data'] = [
        'id' => $image_id,
        'filename' => $filename,
        'path' => $db_path,
        'url' => UPLOAD_URL . $db_path,
        'is_cover' => $is_cover
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
