<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Delete user's messages
    $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);

    // Delete user's listings
    $stmt = $pdo->prepare("DELETE FROM items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    // Delete user account
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    $pdo->commit();

    // Clear session
    session_destroy();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete account: ' . $e->getMessage()
    ]);
}
?> 