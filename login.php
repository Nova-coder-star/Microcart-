<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // or your domain
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'db.php'; // $pdo = PDO instance

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
        echo json_encode(['success' => false, 'message' => 'No account found for this email.']);
        exit;
    }

    // --- Generate API token ---
    $token = bin2hex(random_bytes(32));
    $update = $pdo->prepare("UPDATE sellers SET api_token = ? WHERE id = ?");
    $update->execute([$token, $user['id']]);

    // --- Fetch store ID ---
    $storeStmt = $pdo->prepare("SELECT id FROM storefronts WHERE user_id = ?");
    $storeStmt->execute([$user['id']]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    $storeId = $store['id'] ?? null;

    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'userId' => $user['id'],
        'storeId' => $storeId,
        'brandname' => $user['brandname'],
        'token' => $token
    ]);

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error during login.']);
}
?>