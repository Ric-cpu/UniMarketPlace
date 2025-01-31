<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    if (!isset($_GET['seller_id']) || !isset($_GET['item_id'])) {
        throw new Exception('Missing required parameters');
    }

    $seller_id = (int)$_GET['seller_id'];
    $item_id = (int)$_GET['item_id'];
    $current_user_id = (int)$_SESSION['user_id'];

    // Get seller and item details
    $stmt = $pdo->prepare("
        SELECT 
            i.name as item_name,
            u.first_name,
            u.last_name
        FROM items i
        JOIN users u ON i.user_id = u.id
        WHERE i.id = ? AND i.user_id = ?
    ");
    
    $stmt->execute([$item_id, $seller_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        throw new Exception('Invalid seller or item');
    }

    echo json_encode([
        'success' => true,
        'conversation' => [
            'seller_id' => $seller_id,
            'item_id' => $item_id,
            'seller_name' => $details['first_name'] . ' ' . $details['last_name'],
            'item_name' => $details['item_name']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in init_conversation.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 