<?php
<?php
header('Content-Type: application/json');
require_once 'db.php';

$email = trim($_POST['email'] ?? '');
if (!$email) {
    echo json_encode(['success'=>false,'message'=>'Email missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, brandname FROM sellers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success'=>false,'message'=>'No account found.']);
        exit;
    }

    echo json_encode([
        'success'=>true,
        'message'=>'Login successful',
        'user'=>$user
    ]);

} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Exception: '.$e->getMessage()]);
}
