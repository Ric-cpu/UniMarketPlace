<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Validate input
    if (!isset($_POST['id']) || !isset($_POST['name']) || !isset($_POST['description']) || !isset($_POST['price'])) {
        throw new Exception('Missing required fields');
    }

    // Verify ownership
    $stmt = $pdo->prepare("SELECT user_id FROM items WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $item = $stmt->fetch();

    if (!$item || $item['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized');
    }

    // Handle image upload if provided
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('Invalid file type');
        }

        $new_filename = uniqid() . '.' . $file_extension;
        $image_path = 'uploads/' . $new_filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], '../' . $image_path)) {
            throw new Exception('Failed to upload image');
        }
    }

    // Update item
    $query = "UPDATE items SET name = ?, description = ?, price = ?, category = ?";
    $params = [
        $_POST['name'],
        $_POST['description'],
        floatval($_POST['price']),
        $_POST['category']
    ];

    if ($image_path) {
        $query .= ", image = ?";
        $params[] = $image_path;
    }

    $query .= " WHERE id = ? AND user_id = ?";
    $params[] = $_POST['id'];
    $params[] = $_SESSION['user_id'];

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error in update_listing.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 