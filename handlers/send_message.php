<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Validate required fields
    if (!isset($_POST['receiver_id']) || !isset($_POST['item_id'])) {
        throw new Exception('Missing required fields');
    }

    $receiver_id = (int)$_POST['receiver_id'];
    $item_id = (int)$_POST['item_id'];
    $message = trim($_POST['message'] ?? '');
    $attachment_path = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPEG, PNG and GIF are allowed.');
        }
        
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB limit
            throw new Exception('File size too large. Maximum size is 5MB.');
        }

        $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $attachment_path = 'uploads/' . $new_filename;
        } else {
            throw new Exception('Failed to upload image');
        }
    }

    // Require either a message or an image
    if (empty($message) && !$attachment_path) {
        throw new Exception('Message or image is required');
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (
            sender_id,
            receiver_id,
            item_id,
            message,
            attachment,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $receiver_id,
        $item_id,
        $message,
        $attachment_path
    ]);

    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log("Error in send_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 