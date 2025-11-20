<?php
// ===================================================
// GET USER DETAILS ENDPOINT
// ===================================================

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: https://microcart.pxxl.click");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'db.php'; // contains $pdo

$user_id = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing user ID']);
    exit;
}

// Default profile image (URL)
$defaultProfileImage = 'https://microcart.pxxl.click/default-avatar.png';

try {
    $stmt = $pdo->prepare("
        SELECT id, brandname, whatsapp_number, call_number, profile_image 
        FROM sellers 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Use the URL stored in DB or fallback to default
        $user['profile_image'] = $user['profile_image'] ?: $defaultProfileImage;

        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    error_log("Get User Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error fetching user details']);
}
?>