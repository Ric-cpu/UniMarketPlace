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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['first_name']) || !isset($data['last_name']) || !isset($data['graduation_year'])) {
        throw new Exception('Missing required fields');
    }

    $stmt = $pdo->prepare("
        UPDATE users 
        SET first_name = ?, 
            last_name = ?, 
            graduation_year = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['graduation_year'],
        $_SESSION['user_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in update_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 