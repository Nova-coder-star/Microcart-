<?php
// ===================================================
// DELETE PRODUCT ENDPOINT (MicrocartX)
// Path: /api/delete_product.php
// ===================================================

// --- Headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://microcart.pxxl.click'); // ✅ Replace with your actual frontend domain
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// --- Handle OPTIONS (CORS preflight) ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Include Database Connection ---
require_once 'db.php'; // Uses $pdo from db.php

// --- Parse JSON Body ---
$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['productId'] ?? null;
$userId    = $input['userId'] ?? null;

// --- Validate Product ID ---
if (!$productId || !is_numeric($productId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing product ID.']);
    exit;
}

$productId = (int)$productId;

try {
    // --- Check if Product Exists ---
    $stmt = $pdo->prepare("SELECT user_id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // --- Verify Ownership (if userId provided) ---
    if ($userId && $product['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized to delete this product.']);
        exit;
    }

    // --- Skip Image Deletion (no upload folder needed) ---

    // --- Delete Product Record ---
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    if ($stmt->rowCount() > 0) {
        // --- Update Seller Post Count ---
        $update = $pdo->prepare("
            UPDATE sellers 
            SET post_count = GREATEST(post_count - 1, 0) 
            WHERE id = ?
        ");
        $update->execute([$product['user_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully.',
            'deletedId' => $productId
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product already deleted or not found.']);
    }

} catch (PDOException $e) {
    error_log("Delete Product Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Could not delete product.']);
}
?>