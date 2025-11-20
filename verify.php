<?php
header("Access-Control-Allow-Origin: *");
require 'db.php'; // PDO connection

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    die("Invalid or missing token");
}

try {
    $stmt = $db->prepare("
        SELECT ev.user_id, ev.expires_at, s.verified
        FROM email_verifications ev
        JOIN sellers s ON ev.user_id = s.id
        WHERE ev.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        die("Invalid verification token");
    }

    if (strtotime($record['expires_at']) < time()) {
        die("Verification link has expired.");
    }

    if ($record['verified']) {
        header("Location: https://microcart.pxxl.click/profile?verified=already");
        exit;
    }

    // Mark verified
    $stmt = $db->prepare("UPDATE sellers SET verified = 1 WHERE id = ?");
    $stmt->execute([$record['user_id']]);

    // Delete used token
    $stmt = $db->prepare("DELETE FROM email_verifications WHERE token = ?");
    $stmt->execute([$token]);

    // Redirect to profile
    header("Location: https://microcart.pxxl.click/profile?verified=success");
    exit;
} catch (Exception $e) {
    die("Server error: " . $e->getMessage());
}
?>




