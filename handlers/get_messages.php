<?php
session_start();
require_once '../db_connect.php';

// Debug logging
error_log("Starting get_messages.php");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("GET parameters: " . json_encode($_GET));

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Validate parameters
    if (!isset($_GET['user_id']) || !isset($_GET['item_id'])) {
        throw new Exception('Missing required parameters');
    }

    $current_user_id = (int)$_SESSION['user_id'];
    $other_user_id = (int)$_GET['user_id'];
    $item_id = (int)$_GET['item_id'];

    // Debug logging
    error_log("Fetching messages for: current_user=$current_user_id, other_user=$other_user_id, item=$item_id");

    // Verify the users and item exist
    $verify_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE id = ?) as user1_exists,
            (SELECT COUNT(*) FROM users WHERE id = ?) as user2_exists,
            (SELECT COUNT(*) FROM items WHERE id = ?) as item_exists
    ");
    
    $verify_stmt->execute([$current_user_id, $other_user_id, $item_id]);
    $verify_result = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verify_result['user1_exists'] || !$verify_result['user2_exists'] || !$verify_result['item_exists']) {
        throw new Exception('Invalid user or item ID');
    }

    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.first_name as sender_name, u.id as sender_id, u.username as sender_username
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ? AND m.item_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ? AND m.item_id = ?)
        ORDER BY m.created_at ASC
    ");

    $stmt->execute([
        $current_user_id,
        $other_user_id,
        $item_id,
        $other_user_id,
        $current_user_id,
        $item_id
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug logging
    error_log("Found " . count($messages) . " messages");

    // Mark messages as read
    $update_stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE receiver_id = ? 
        AND sender_id = ? 
        AND item_id = ?
        AND is_read = 0
    ");

    $update_stmt->execute([$current_user_id, $other_user_id, $item_id]);

    // Always return a success response with an empty array if no messages
    echo json_encode([
        'success' => true,
        'messages' => $messages ?: [] // Ensure we always return an array
    ]);

} catch (Exception $e) {
    error_log("Error in get_messages.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch messages: ' . $e->getMessage()
    ]);
}
?> 