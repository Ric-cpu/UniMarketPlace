<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    error_log("Not authenticated in get_user_profile.php");
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    if (!isset($_GET['username']) && !isset($_GET['user_id'])) {
        error_log("No username or user_id provided");
        throw new Exception('Username or User ID is required');
    }

    $whereClause = isset($_GET['username']) ? "u.username = ?" : "u.id = ?";
    $param = isset($_GET['username']) ? $_GET['username'] : $_GET['user_id'];

    error_log("Searching for user with param: " . $param);

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.first_name,
            u.last_name,
            u.created_at,
            (SELECT COUNT(*) FROM items WHERE user_id = u.id) as listings_count,
            COALESCE((SELECT AVG(rating) FROM ratings WHERE rated_user_id = u.id), 0) as rating
        FROM users u
        WHERE $whereClause
    ");
    
    $stmt->execute([$param]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Query result: " . json_encode($user));

    if (!$user) {
        error_log("User not found for param: " . $param);
        throw new Exception('User not found');
    }

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (Exception $e) {
    error_log("Error in get_user_profile.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 