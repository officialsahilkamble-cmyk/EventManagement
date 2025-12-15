<?php
/**
 * Simple QR Code Generator using Google Charts API
 * This creates QR codes as images
 */

if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Generate QR Code image
 * @param string $data Data to encode in QR code
 * @param int $size Size of QR code (default 300x300)
 * @return string Base64 encoded image data
 */
function generateQRCode($data, $size = 300)
{
    // Use Google Charts API to generate QR code
    $url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data) . "&choe=UTF-8";

    // Get the image data
    $imageData = @file_get_contents($url);

    if ($imageData === false) {
        // Fallback: Create a simple SVG QR code placeholder
        return generateSVGQRCode($data, $size);
    }

    return $imageData;
}

/**
 * Save QR Code to file
 * @param string $data Data to encode
 * @param string $filepath Path to save the QR code
 * @param int $size Size of QR code
 * @return bool Success status
 */
function saveQRCode($data, $filepath, $size = 300)
{
    $imageData = generateQRCode($data, $size);

    if ($imageData) {
        // Ensure directory exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($filepath, $imageData) !== false;
    }

    return false;
}

/**
 * Generate SVG QR Code as fallback
 * @param string $data Data to encode
 * @param int $size Size of QR code
 * @return string SVG image data
 */
function generateSVGQRCode($data, $size = 300)
{
    // Simple SVG placeholder with ticket ID
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#ffffff"/>
        <rect x="10" y="10" width="' . ($size - 20) . '" height="' . ($size - 20) . '" fill="none" stroke="#000000" stroke-width="2"/>
        <text x="50%" y="50%" text-anchor="middle" font-family="monospace" font-size="14" fill="#000000">' . htmlspecialchars($data) . '</text>
        <text x="50%" y="60%" text-anchor="middle" font-family="Arial" font-size="12" fill="#666666">Scan to Verify</text>
    </svg>';

    return $svg;
}

/**
 * Get QR Code as base64 data URL
 * @param string $data Data to encode
 * @param int $size Size of QR code
 * @return string Base64 data URL
 */
function getQRCodeDataURL($data, $size = 300)
{
    $imageData = generateQRCode($data, $size);
    return 'data:image/png;base64,' . base64_encode($imageData);
}
