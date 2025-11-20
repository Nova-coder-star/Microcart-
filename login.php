<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once 'db.php'; // $pdo

$email = trim($_POST['email'] ?? '');
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, brandname FROM sellers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No account found.']);
        exit;
    }

    // --- Get store ---
    $storeStmt = $pdo->prepare("SELECT id FROM storefronts WHERE user_id = ?");
    $storeStmt->execute([$user['id']]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    $storeId = $store['id'] ?? null;

    echo json_encode([
        'success' => true,
        'message' => 'Login successful (email only).',
        'userId' => $user['id'],
        'storeId' => $storeId,
        'brandname' => $user['brandname']
    ]);

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error during login.']);
}
