<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['user_id']) || !isset($_GET['item_id']) || !isset($_GET['last_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM messages
        WHERE (
            (sender_id = ? AND receiver_id = ?)
            OR 
            (sender_id = ? AND receiver_id = ?)
        )
        AND item_id = ?
        AND id > ?
    ");

    $stmt->execute([
        $_SESSION['user_id'], $_GET['user_id'],
        $_GET['user_id'], $_SESSION['user_id'],
        $_GET['item_id'],
        $_GET['last_id']
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'new_messages' => $result['count'] > 0
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check messages'
    ]);
}
?> 