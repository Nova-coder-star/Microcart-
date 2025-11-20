<?php
header('Content-Type: application/json');
require 'db.php'; // Make sure $pdo is available here

// Base URL for uploaded images (no uploads folder)
$baseUrl = 'https://microcart.pxxl.click/';

// Get user ID and uploaded file
$user_id = $_POST['userId'] ?? '';
$file = $_FILES['profileImage'] ?? null;

if (!$user_id || !$file) {
    echo json_encode(['success' => false, 'message' => 'Missing user ID or image']);
    exit;
}

// Check for upload errors
if ($file['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'File upload error']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed']);
    exit;
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; 
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB allowed']);
    exit;
}

// Save file to the ROOT directory of the project
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid('profile_', true) . '.' . $extension;
$targetPath = __DIR__ . '/' . $filename;  // <-- root of project

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $imageUrl = $baseUrl . $filename; // URL to return to frontend

    try {
        // Update in sellers table
        $stmt = $pdo->prepare("UPDATE sellers SET profile_image = ? WHERE id = ?");
        $stmt->execute([$imageUrl, $user_id]);

        echo json_encode(['success' => true, 'imageUrl' => $imageUrl]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}

?>