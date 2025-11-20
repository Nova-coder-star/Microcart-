<?php
// config.php
// Central configuration file for Microcart API

// --- Load environment variables if available ---
$env = [];
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_TYPED);
}

// --- Base URL of the application ---
define('BASE_URL', rtrim($env['BASE_URL'] ?? 'https://microcart.pxxl.click', '/'));

// --- Default directories (no local uploads needed) ---
define('UPLOADS_DIR', null); // no local uploads folder
define('PROFILE_IMAGES_URL', BASE_URL . '/profile_images/');
define('PRODUCT_IMAGES_URL', BASE_URL . '/product_images/');

// --- Default placeholder images ---
define('DEFAULT_PROFILE_IMAGE', PROFILE_IMAGES_URL . 'default-avatar.png');
define('DEFAULT_PRODUCT_IMAGE', PRODUCT_IMAGES_URL . 'default-product.png');

// --- Application limits ---
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);  // 5MB, kept for reference if using cloud storage
define('MAX_PRODUCT_IMAGES', 2);              // maximum images per product
define('FREEMIUM_POST_LIMIT', 25);            // max products for free users

// --- Optional: other global settings ---
// define('API_KEY', $env['API_KEY'] ?? null); // example for future use
?>