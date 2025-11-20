<?php
$host = "db.pxxl.pro";
$port = 56224;
$db   = "db_695c9cfd";
$user = "user_71553f4a";
$pass = "789304d582936cd894ab650416050655";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    echo "CONNECTED SUCCESSFULLY";
} catch (PDOException $e) {
    echo "CONNECTION FAILED: " . $e->getMessage();
}
