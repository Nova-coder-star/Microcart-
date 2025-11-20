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

// --- Read JSON body ---
$input = json_decode(file_get_contents('php://input'), true);
$user_id     = $input['user_id'] ?? '';
$title       = trim($input['title'] ?? '');
$price       = trim($input['price'] ?? '');
$description = trim($input['description'] ?? '');
$images      = $input['images'] ?? []; // Array of image URLs

// --- Validate required fields ---
if (!$user_id || !$title || !$price || !$description || !is_array($images) || count($images) === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields or images.']);
    exit;
}

// --- Clean price ---
$priceClean = number_format((float)preg_replace("/[^0-9.]/", "", $price), 2, '.', '');
if (!is_numeric($priceClean) || $priceClean < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid price value.']);
    exit;
}

// --- Sanitize text ---
$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

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

    // --- Limit images to 2 ---
    $imagePaths = array_slice($images, 0, 2);

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