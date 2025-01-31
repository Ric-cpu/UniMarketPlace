<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug logging
    error_log("Received message data: " . json_encode($input));

    // Validate required fields
    if (!isset($input['receiver_id']) || !isset($input['item_id']) || !isset($input['message'])) {
        throw new Exception('Missing required fields');
    }

    // Convert to integers
    $receiver_id = (int)$input['receiver_id'];
    $item_id = (int)$input['item_id'];
    $message = trim($input['message']);

    // Additional validation
    if ($receiver_id <= 0 || $item_id <= 0 || empty($message)) {
        throw new Exception('Invalid input data');
    }

    // Debug logging
    error_log("Sending message: sender_id={$_SESSION['user_id']}, receiver_id={$receiver_id}, item_id={$item_id}");

    $stmt = $pdo->prepare("
        INSERT INTO messages (
            sender_id,
            receiver_id,
            item_id,
            message,
            created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $receiver_id,
        $item_id,
        $message
    ]);

    $message_id = $pdo->lastInsertId();
    
    // Debug logging
    error_log("Message created with ID: " . $message_id);

    echo json_encode([
        'success' => true,
        'message_id' => $message_id
    ]);

} catch (Exception $e) {
    error_log("Error in send_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 