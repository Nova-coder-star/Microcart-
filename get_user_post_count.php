<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://microcart.pxxl.click');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing user ID']);
    exit;
}

// TODO: Optional authentication check

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS postCount FROM products WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'postCount' => (int)($result['postCount'] ?? 0)
    ]);
} catch (PDOException $e) {
    error_log("Get User Post Count Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while fetching post count']);
}
?>