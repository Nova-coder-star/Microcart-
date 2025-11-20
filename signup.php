<?php
// --- HEADERS ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
require 'db.php'; // $db = PDO instance

// --- ENSURE POST REQUEST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// --- REQUIRED FIELDS ---
$requiredFields = ['brandname', 'email', 'location', 'call_number', 'whatsapp_number'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// --- SANITIZE INPUTS ---
$brandname       = trim($_POST['brandname']);
$email           = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
$location        = trim($_POST['location']);
$address         = isset($_POST['address']) ? trim($_POST['address']) : null;
$call_number     = trim($_POST['call_number']);
$whatsapp_number = trim($_POST['whatsapp_number']);

// --- CHECK IF EMAIL EXISTS ---
$stmt = $db->prepare("SELECT id FROM sellers WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// --- SLUGIFY FUNCTION ---
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text ?: 'store-' . uniqid();
}
$slug = slugify($brandname);

// --- HANDLE LOGO UPLOAD (optional) ---
$logoPath = null;
if (!empty($_FILES['logo']['name'])) {

    if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Logo file too large (max 2MB)']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['logo']['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mimeType, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Unsupported logo format']);
        exit;
    }

    $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $filename  = uniqid('logo_', true) . '.' . $extension;
    $target    = __DIR__ . '/' . $filename;

    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload logo']);
        exit;
    }

    $logoPath = "https://microcart.pxxl.click/" . $filename;
}

// --- INSERT SELLER (NO PASSWORD) ---
$stmt = $db->prepare("
    INSERT INTO sellers (brandname, email, call_number, whatsapp_number, address, verified)
    VALUES (?, ?, ?, ?, ?, 0)
");
$stmt->execute([$brandname, $email, $call_number, $whatsapp_number, $address]);
$userId = $db->lastInsertId();

// --- INSERT STOREFRONT ---
$stmt = $db->prepare("
    INSERT INTO storefronts (name, logo, slug, location, user_id)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$brandname, $logoPath, $slug, $location, $userId]);
$storeId = $db->lastInsertId();

// --- EMAIL VERIFY TOKEN ---
$verifyToken = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

$stmt = $db->prepare("
    INSERT INTO email_verifications (user_id, token, expires_at)
    VALUES (?, ?, ?)
");
$stmt->execute([$userId, $verifyToken, $expiresAt]);

$verifyLink = "https://microcart.pxxl.click/verify.php?token=$verifyToken";
$subject = "Verify your Microcart account";
$message = "Hi $brandname,\n\nPlease verify your account:\n$verifyLink\n\nExpires in 24 hours.";
$headers = "From: no-reply@microcart.com\r\n";

@mail($email, $subject, $message, $headers);

// --- API TOKEN ---
$apiToken = bin2hex(random_bytes(32));
$stmt = $db->prepare("UPDATE sellers SET api_token = ? WHERE id = ?");
$stmt->execute([$apiToken, $userId]);

// --- RESPONSE ---
echo json_encode([
    'success' => true,
    'message' => 'Signup successful! Verification email sent.',
    'userId'  => $userId,
    'storeId' => $storeId,
    'token'   => $apiToken
]);
?>