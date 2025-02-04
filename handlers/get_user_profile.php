<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

// Debug log for incoming request
error_log("GET params: " . json_encode($_GET));
error_log("Session user_id: " . $_SESSION['user_id']);

if (!isset($_SESSION['user_id'])) {
    error_log("Not authenticated in get_user_profile.php");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    if (!isset($_GET['username']) && !isset($_GET['user_id'])) {
        error_log("No username or user_id provided");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username or User ID is required']);
        exit();
    }

    $whereClause = isset($_GET['username']) ? "u.username = ?" : "u.id = ?";
    $param = isset($_GET['username']) ? $_GET['username'] : $_GET['user_id'];

    error_log("Searching for user with param: " . $param);

    $query = "
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
    ";

    error_log("Query: " . $query);

    $stmt = $pdo->prepare($query);
    $stmt->execute([$param]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Query result: " . json_encode($user));

    if (!$user) {
        error_log("User not found for param: " . $param);
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'user' => $user
    ]);

} catch (Exception $e) {
    error_log("Error in get_user_profile.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 