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
        SELECT 
            i.*,
            u.first_name,
            u.last_name,
            u.email,
            u.graduation_year,
            ic.category,
            (i.user_id = ?) as is_owner
        FROM items i
        JOIN users u ON i.user_id = u.id
        LEFT JOIN item_categories ic ON i.id = ic.item_id
        WHERE i.id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found');
    }

    // Add a flag to indicate if the current user is the owner
    $item['is_owner'] = (bool)$item['is_owner'];

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