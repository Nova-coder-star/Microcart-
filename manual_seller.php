<?php
require 'db.php'; // your working PDO connection

// ======= SELLER DATA =======
$brandname       = 'Test Brand';
$email           = 'testbrand@example.com'; // use this to log in
$call_number     = '08012345678';
$whatsapp_number = '08012345678';
$address         = 'Lagos';
$profile_image   = '/default-avatar.png';
$location        = 'Lagos';

// ======= CREATE API TOKEN =======
$api_token = bin2hex(random_bytes(16)); // 32-char token

try {
    // --- Check if email already exists ---
    $check = $pdo->prepare("SELECT id FROM sellers WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "Seller already exists with email: $email";
        exit;
    }

    // --- Insert seller (no password needed for Phase 1) ---
    $stmt = $pdo->prepare("
        INSERT INTO sellers 
        (brandname, email, call_number, whatsapp_number, address, api_token, verified, profile_image)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?)
    ");
    $stmt->execute([$brandname, $email, $call_number, $whatsapp_number, $address, $api_token, $profile_image]);
    $seller_id = $pdo->lastInsertId();

    // --- Insert storefront ---
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $brandname));
    $stmt = $pdo->prepare("
        INSERT INTO storefronts (name, logo, slug, location, user_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$brandname, '/default-logo.png', $slug, $location, $seller_id]);

    echo "✅ Seller and storefront created successfully!\n";
    echo "Seller ID: $seller_id\n";
    echo "Login email: $email\n";
    echo "You can log in using email only (no password required for now).";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
