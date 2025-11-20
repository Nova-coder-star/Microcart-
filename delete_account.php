<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://microcart.pxxl.click');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing user ID']);
    exit;
}

// TODO: Add authentication/authorization check here

try {
    // Optional: Delete related products first if needed
    // $pdo->prepare("DELETE FROM products WHERE user_id = ?")->execute([$user_id]);

    $stmt = $pdo->prepare("DELETE FROM sellers WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No account found or already deleted.']);
    }
} catch (PDOException $e) {
    error_log("Delete Account Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: could not delete account.']);
}
?>

