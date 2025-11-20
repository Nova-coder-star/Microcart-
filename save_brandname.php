<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://microcart.pxxl.click');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php'; // $pdo

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['userId'] ?? '';
$brandName = $data['brandName'] ?? '';

if (!$user_id || !$brandName) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user ID or brand name']);
    exit;
}

// Sanitize input
$brandName = substr(trim($brandName), 0, 255);

try {
    $stmt = $pdo->prepare("UPDATE sellers SET brandname = ? WHERE id = ?");
    $ok = $stmt->execute([$brandName, $user_id]);

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Brand name updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update brand name']);
    }

} catch (PDOException $e) {
    error_log("Save Brandname Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>