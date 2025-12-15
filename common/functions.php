<?php
/**
 * Common Functions & Utilities
 */

defined('APP_ACCESS') or die('Direct access not permitted');

/**
 * Sanitize output to prevent XSS
 */
function esc($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate unique ticket ID
 */
function generateTicketID()
{
    return 'EVT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate unique booking reference
 */
function generateBookingRef()
{
    return 'BK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
}

/**
 * Format currency
 */
function formatCurrency($amount)
{
    // Indian Rupee format with comma separation (e.g., ₹1,00,000.00)
    return '₹' . number_format($amount, 2, '.', ',');
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y')
{
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A')
{
    return date($format, strtotime($datetime));
}

/**
 * Upload file with validation
 */
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'])
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed with error code: ' . $file['error']];
    }

    // Validate file size (5MB max)
    if ($file['size'] > 5242880) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];

    if (!array_key_exists($mimeType, $allowedMimes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Generate unique filename
    $extension = $allowedMimes[$mimeType];
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . $filename;

    // Create directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}


/**
 * Generate Barcode (SVG)
 */
function generateBarcode($data, $width = 200, $height = 50)
{
    // Simple barcode representation
    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect width="100%" height="100%" fill="#fff"/>';

    // Generate bars based on data
    $barWidth = $width / strlen($data);
    for ($i = 0; $i < strlen($data); $i++) {
        $x = $i * $barWidth;
        if (ord($data[$i]) % 2 == 0) {
            $svg .= '<rect x="' . $x . '" y="0" width="' . $barWidth . '" height="' . ($height - 15) . '" fill="#000"/>';
        }
    }

    $svg .= '<text x="50%" y="' . ($height - 2) . '" text-anchor="middle" font-family="monospace" font-size="10">' . $data . '</text>';
    $svg .= '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Send Email (Basic implementation)
 */
function sendEmail($to, $subject, $body, $isHTML = true)
{
    // In production, use PHPMailer with SMTP settings from database
    $headers = "From: " . getSetting('contact_email', 'noreply@eventmanage.com') . "\r\n";
    if ($isHTML) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }

    return mail($to, $subject, $body, $headers);
}

/**
 * Redirect helper
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Set flash message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

/**
 * Get current user
 */
function getCurrentUser()
{
    global $pdo;
    if (!isLoggedIn())
        return null;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Get current admin
 */
function getCurrentAdmin()
{
    global $pdo;
    if (!isAdminLoggedIn())
        return null;

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}

/**
 * Slugify string
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    return empty($text) ? 'n-a' : $text;
}

/**
 * Paginate results
 */
function paginate($totalItems, $itemsPerPage = 10, $currentPage = 1)
{
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;

    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset
    ];
}
