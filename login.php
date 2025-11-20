<?php
// ===================================================
// LOGIN ENDPOINT
// Path: /api/login.php
// ===================================================

// --- Headers ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://microcart.pxxl.click');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// --- Handle OPTIONS for CORS ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Include Database ---
require_once 'db.php'; // should define $pdo

// --- Get POST data ---
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

try {
    // --- Fetch user ---
    $stmt = $pdo->prepare("SELECT id, brandname, password FROM sellers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No account found for this email.']);
        exit;
    }

    // --- Verify password ---
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit;
    }

    // --- Generate new API token ---
    $token = bin2hex(random_bytes(32));
    $update = $pdo->prepare("UPDATE sellers SET api_token = ? WHERE id = ?");
    $update->execute([$token, $user['id']]);

    // --- Get store ID ---
    $storeStmt = $pdo->prepare("SELECT id FROM storefronts WHERE user_id = ?");
    $storeStmt->execute([$user['id']]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    $storeId = $store['id'] ?? null;

    // --- Response ---
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
