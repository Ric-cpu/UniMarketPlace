<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = (int)$_GET['user_id'];

    // Get user's active listings
    $stmt = $pdo->prepare("
        SELECT i.*, ic.category 
        FROM items i
        LEFT JOIN item_categories ic ON i.id = ic.item_id
        WHERE i.user_id = ?
        ORDER BY i.created_at DESC
    ");
    
    $stmt->execute([$userId]);
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'listings' => $listings
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 