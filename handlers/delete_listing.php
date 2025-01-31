<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('Missing item ID');
    }

    // Verify ownership and get image path
    $stmt = $pdo->prepare("SELECT user_id, image FROM items WHERE id = ?");
    $stmt->execute([$data['id']]);
    $item = $stmt->fetch();

    if (!$item || $item['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized');
    }

    // Delete associated messages first
    $stmt = $pdo->prepare("DELETE FROM messages WHERE item_id = ?");
    $stmt->execute([$data['id']]);

    // Delete the item
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['id'], $_SESSION['user_id']]);

    // Delete the image file if it exists
    if ($item['image'] && file_exists('../' . $item['image'])) {
        unlink('../' . $item['image']);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error in delete_listing.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 