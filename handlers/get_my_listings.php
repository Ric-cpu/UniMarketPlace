<?php
session_start();
require_once '../db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug logging
error_log("Starting get_my_listings.php");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Debug database connection
    error_log("Testing database connection");
    $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    
    error_log("Fetching listings for user_id: " . $_SESSION['user_id']);

    // Get the listings with proper error handling
    $stmt = $pdo->prepare("
        SELECT i.*, ic.category 
        FROM items i
        LEFT JOIN item_categories ic ON i.id = ic.item_id
        WHERE i.user_id = ?
        ORDER BY i.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($items) . " items");

    echo json_encode([
        'success' => true,
        'items' => $items,
        'debug_info' => [
            'user_id' => $_SESSION['user_id'],
            'item_count' => count($items)
        ]
    ]);

} catch (PDOException $e) {
    error_log("PDO Error in get_my_listings.php: " . $e->getMessage());
    error_log("PDO Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug_message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get_my_listings.php: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch listings'
    ]);
}
?> 