<?php
header('Content-Type: application/json');
include 'db.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

$user_id = $data['userId'] ?? '';
$whatsappNumber = $data['whatsappNumber'] ?? '';

if (!$user_id || !$whatsappNumber) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

// Simple validation
$whatsappNumber = trim($whatsappNumber);

if (!preg_match('/^[0-9+\s]+$/', $whatsappNumber)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE sellers SET whatsapp_number = ? WHERE id = ?");
    $stmt->execute([$whatsappNumber, $user_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found or no changes made']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
