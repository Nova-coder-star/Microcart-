<?php
// ===================================================
// GET PRODUCTS ENDPOINT
// Path: /api/get_products.php
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

// --- Get storeId from query ---
$storeId = $_GET['storeId'] ?? '';

if (!$storeId || !is_numeric($storeId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid store ID']);
    exit;
}

$storeId = (int)$storeId;

try {
    // --- Fetch products for the store ---
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.price, p.description, p.images
        FROM products p
        JOIN sellers s ON p.user_id = s.id
        JOIN storefronts sf ON sf.user_id = s.id
        WHERE sf.id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$storeId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Convert JSON images to array without modifying URLs ---
    foreach ($products as &$product) {
        $product['images'] = !empty($product['images']) 
            ? json_decode($product['images'], true) 
            : [];
    }

    echo json_encode([
        'success' => true,
        'data' => $products
    ]);

} catch (PDOException $e) {
    error_log("Get Products Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: could not fetch products.']);
}
?>