<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

require_once 'db.php';

// --- Ensure user is logged in ---
$user_id = $_POST['user_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$price = trim($_POST['price'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$user_id || !$title || !$price || !$description) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// --- Sanitize text ---
$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

// --- Clean price ---
$priceClean = number_format((float)preg_replace("/[^0-9.]/", "", $price), 2, '.', '');
if (!is_numeric($priceClean) || $priceClean < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid price value.']);
    exit;
}

try {
    // --- Check user ---
    $stmt = $pdo->prepare("SELECT post_count FROM sellers WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // --- Freemium post limit ---
    $maxPosts = 25;
    if ((int)$user['post_count'] >= $maxPosts) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => "Post limit reached ({$maxPosts} items max)."]);
        exit;
    }

    // --- Handle image uploads ---
    $uploadDir = __DIR__ . "/upload/$user_id/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $imagePaths = [];
    $maxImages = 2;
    for ($i = 0; $i < $maxImages; $i++) {
        if (isset($_FILES["image_$i"]) && $_FILES["image_$i"]["error"] === UPLOAD_ERR_OK) {
            $file = $_FILES["image_$i"];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => $file['name'] . ' has unsupported format.']);
                exit;
            }
            if ($file['size'] > 3 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => $file['name'] . ' exceeds 3MB.']);
                exit;
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_', true) . "." . $ext;
            $target = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $target)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload ' . $file['name']]);
                exit;
            }

            $imagePaths[] = "https://microcart.pxxl.click/upload/$user_id/$filename";
        }
    }

    if (count($imagePaths) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'At least one image is required.']);
        exit;
    }

    // --- Insert product ---
    $stmt = $pdo->prepare("INSERT INTO products (user_id, title, price, description, images) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $priceClean, $description, json_encode($imagePaths)]);
    $productId = $pdo->lastInsertId();

    // --- Increment post count ---
    $pdo->prepare("UPDATE sellers SET post_count = post_count + 1 WHERE id = ?")->execute([$user_id]);
    $newPostCount = (int)$user['post_count'] + 1;

    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully.',
        'id' => $productId,
        'images' => $imagePaths,
        'postCount' => $newPostCount
    ]);

} catch (PDOException $e) {
    error_log("Add Product Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while adding product.']);
}
?>