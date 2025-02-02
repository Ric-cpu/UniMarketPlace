<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Missing item ID');
    }

    $stmt = $pdo->prepare("
        SELECT i.*, 
               u.first_name, 
               u.last_name, 
               u.email, 
               u.graduation_year,
               ic.category,
               GROUP_CONCAT(DISTINCT im.image_path ORDER BY im.display_order) as additional_images
        FROM items i
        LEFT JOIN users u ON i.user_id = u.id
        LEFT JOIN item_categories ic ON i.id = ic.item_id
        LEFT JOIN item_images im ON i.id = im.item_id
        WHERE i.id = ?
        GROUP BY i.id
    ");
    
    $stmt->execute([$_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found');
    }

    // Create array of all images
    $images = [$item['image']]; // Start with main image
    if ($item['additional_images']) {
        $additional_images = explode(',', $item['additional_images']);
        $images = array_merge($images, $additional_images);
    }
    $item['images'] = array_values(array_unique(array_filter($images)));
    unset($item['additional_images']);

    // Add a flag to indicate if the current user is the owner
    $item['is_owner'] = ($item['user_id'] == $_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'item' => $item
    ]);

} catch (Exception $e) {
    error_log("Error in get_item_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 