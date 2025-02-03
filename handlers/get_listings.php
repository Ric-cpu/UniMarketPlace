<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Debug logging
    error_log("Starting get_listings.php with params: " . json_encode($_GET));

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 12;
    $offset = ($page - 1) * $items_per_page;

    // Build the base query with JOIN to item_categories
    $query = "SELECT i.id, i.user_id, i.name, i.price, i.description, ic.category, i.image, 
                     i.created_at, u.first_name, u.last_name
              FROM items i 
              JOIN users u ON i.user_id = u.id 
              LEFT JOIN item_categories ic ON i.id = ic.item_id 
              WHERE 1=1";
    $params = [];

    // Add search filter if present
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . trim($_GET['search']) . '%';
        $query .= " AND (i.name LIKE ? OR i.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        error_log("Added search filter: " . $search);
    }

    // Add category filter if present
    if (isset($_GET['category']) && !empty($_GET['category']) && $_GET['category'] !== 'Uncategorized') {
        $query .= " AND ic.category = ?";
        $params[] = $_GET['category'];
        error_log("Added category filter: " . $_GET['category']);
    }

    // Add sorting
    $query .= " ORDER BY i.created_at DESC";

    // Get total count for pagination
    $count_query = preg_replace(
        '/SELECT.*?FROM/',
        'SELECT COUNT(DISTINCT i.id) FROM',
        $query
    );
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_items = $count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $items_per_page);

    // Add pagination using integer parameters
    $query .= " LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;

    // Debug logging
    error_log("Final query: " . $query);
    error_log("Parameters: " . json_encode($params));

    // Execute final query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map the categories to ensure proper format
    $items = array_map(function($item) {
        // Use the category from item_categories table, fallback to 'Uncategorized'
        $item['category'] = $item['category'] ?? 'Uncategorized';
        return $item;
    }, $items);

    // Debug logging
    error_log("Found " . count($items) . " items");

    echo json_encode([
        'success' => true,
        'items' => $items,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_items' => $total_items
    ]);

} catch (Exception $e) {
    error_log("Error in get_listings.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch listings: ' . $e->getMessage()
    ]);
}
?> 