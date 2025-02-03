<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Get distinct categories from item_categories table
    $stmt = $pdo->query("SELECT DISTINCT category FROM item_categories ORDER BY category ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    error_log("Error in get_categories.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch categories'
    ]);
}
?> 