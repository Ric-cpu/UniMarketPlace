<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $current_user_id = $_SESSION['user_id'];
    
    // Debug logging
    error_log("Getting conversations for user_id: " . $current_user_id);

    // Get all conversations
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END as other_user_id,
            m.item_id,
            i.name as item_name,
            u.first_name as other_user_name,
            u.last_name as other_user_lastname,
            (
                SELECT message
                FROM messages m2
                WHERE ((m2.sender_id = m.sender_id AND m2.receiver_id = m.receiver_id)
                    OR (m2.sender_id = m.receiver_id AND m2.receiver_id = m.sender_id))
                    AND m2.item_id = m.item_id
                ORDER BY m2.created_at DESC
                LIMIT 1
            ) as last_message,
            EXISTS (
                SELECT 1
                FROM messages m3
                WHERE m3.receiver_id = ?
                AND m3.is_read = 0
                AND m3.item_id = m.item_id
                AND m3.sender_id = CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END
            ) as has_unread
        FROM messages m
        JOIN items i ON m.item_id = i.id
        JOIN users u ON (
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END = u.id
        )
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY m.item_id, other_user_id
        ORDER BY MAX(m.created_at) DESC
    ");

    $stmt->execute([
        $current_user_id,
        $current_user_id,
        $current_user_id,
        $current_user_id,
        $current_user_id,
        $current_user_id
    ]);

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug logging
    error_log("Found " . count($conversations) . " conversations");

    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);

} catch (Exception $e) {
    error_log("Error in get_conversations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch conversations: ' . $e->getMessage()
    ]);
}
?> 