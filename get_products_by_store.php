<?php
// ===================================================
// GET PRODUCTS BY STORE ENDPOINT
// Path: /api/get_products_by_store.php
// ===================================================

// --- Headers ---
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: https://microcart.pxxl.click");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// --- Handle OPTIONS for CORS ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Include Database ---
require_once 'db.php'; // contains $pdo (PDO connection)

// --- Get store slug from query ---
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Store slug missing']);
    exit;
}

// --- Default URLs ---
$defaultProductImg = 'https://microcart.pxxl.click/default-product.png';
$defaultLogoImg = 'https://microcart.pxxl.click/default-logo.png';

try {
    // --- Fetch store info ---
    $stmt = $pdo->prepare("
        SELECT sf.id AS store_id, sf.name AS store_name, sf.logo AS store_logo,
               s.whatsapp_number AS whatsapp, s.call_number AS call_number, s.id AS user_id
        FROM storefronts sf
        JOIN sellers s ON s.id = sf.user_id
        WHERE sf.slug = ?
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $store = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$store) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Store not found']);
        exit;
    }

    // --- Fetch products for this seller ---
    $stmt = $pdo->prepare("
        SELECT id, title, price, description, images
        FROM products
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$store['user_id']]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Decode images without modifying URLs ---
    foreach ($products as &$product) {
        $product['images'] = !empty($product['images']) 
            ? json_decode($product['images'], true) 
            : [$defaultProductImg];

        // Ensure array has at least one image
        if (!is_array($product['images']) || count($product['images']) === 0) {
            $product['images'] = [$defaultProductImg];
        }
    }

    // --- Final response ---
    echo json_encode([
        'success' => true,
        'store' => [
            'id' => $store['store_id'],
            'name' => $store['store_name'],
            'logo' => $store['store_logo'] ?? $defaultLogoImg,
            'whatsapp' => $store['whatsapp'] ?? '',
            'call' => $store['call_number'] ?? ''
        ],
        'products' => $products
    ]);

} catch (PDOException $e) {
    error_log("Get Products By Store Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error fetching store products.']);
}
?>