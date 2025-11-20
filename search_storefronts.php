<?php
// ===================================================
// SEARCH STOREFRONTS ENDPOINT
// ===================================================

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php'; // PDO instance $db

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if ($query !== "") {
        $stmt = $db->prepare("
            SELECT sf.id, sf.name, sf.logo, sf.slug, sf.location,
                   s.whatsapp_number AS whatsapp, s.call_number AS call
            FROM storefronts sf
            JOIN sellers s ON s.id = sf.user_id
            WHERE sf.name LIKE :term OR sf.location LIKE :term
            ORDER BY sf.name ASC
        ");
        $stmt->execute([':term' => "%$query%"]);
    } else {
        $stmt = $db->query("
            SELECT sf.id, sf.name, sf.logo, sf.slug, sf.location,
                   s.whatsapp_number AS whatsapp, s.call_number AS call
            FROM storefronts sf
            JOIN sellers s ON s.id = sf.user_id
            ORDER BY sf.id DESC
        ");
    }

    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Correct defaults for root-based system
    foreach ($stores as &$store) {
        if (empty($store['logo'])) {
            // direct URL in ROOT
            $store['logo'] = "https://microcart.pxxl.click/default-logo.png";
        }
        $store['name']     = $store['name']     ?: 'Unnamed Store';
        $store['location'] = $store['location'] ?: 'Unknown location';
        $store['whatsapp'] = $store['whatsapp'] ?: '';
        $store['call']     = $store['call']     ?: '';
    }

    echo json_encode([
        'success' => true,
        'count' => count($stores),
        'data' => $stores
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>