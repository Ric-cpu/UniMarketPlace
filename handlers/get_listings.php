<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Simple query to get all items
    $query = "
        SELECT i.*, ic.category 
        FROM items i 
        LEFT JOIN item_categories ic ON i.id = ic.item_id 
        ORDER BY i.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total_pages' => 1,
        'current_page' => 1
    ]);

} catch (Exception $e) {
    error_log("Error in get_listings.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 