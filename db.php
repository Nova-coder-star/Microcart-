<?php
// ===========================================
// DATABASE CONNECTION (PDO)
// ===========================================

// Your actual database credentials:
$host = "db.pxxl.pro";

$port = "47334";

$dbname = "db_c8184a7e";

$user = "user_ce386a51";

$pass = "9df476a7e863df64c96e8416f7c012ad";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
