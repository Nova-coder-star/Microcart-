<?php
require 'db.php'; // your PDO connection

try {
    $stmt = $pdo->query("SELECT NOW() AS server_time");
    $row = $stmt->fetch();
    echo "✅ DB Connection Successful! Server time: " . $row['server_time'];
} catch (Exception $e) {
    echo "❌ DB Connection Failed: " . $e->getMessage();
}
?>
