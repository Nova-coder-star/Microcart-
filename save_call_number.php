<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['userId'] ?? '';
$callNumber = $data['callNumber'] ?? '';

if (!$user_id || !$callNumber) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

// Optional: sanitize/validate phone number
$callNumber = trim($callNumber);

try {
    // Update the correct table
    $stmt = $pdo->prepare("UPDATE sellers SET call_number = ? WHERE id = ?");
    $ok = $stmt->execute([$callNumber, $user_id]);
    
    echo json_encode(['success' => $ok]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: '.$e->getMessage()]);
}
?>