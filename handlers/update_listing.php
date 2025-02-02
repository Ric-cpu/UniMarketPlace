<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    if (!isset($_POST['id'])) {
        throw new Exception('Missing listing ID');
    }

    $pdo->beginTransaction();

    // First, verify the listing belongs to the user
    $stmt = $pdo->prepare("SELECT user_id FROM items WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $item = $stmt->fetch();

    if (!$item || $item['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access to this listing');
    }

    // Handle image upload if provided
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'uploads/' . $new_filename;
        }
    }

    // Update item details
    $sql = "UPDATE items SET name = ?, price = ?, description = ?";
    $params = [$_POST['name'], $_POST['price'], $_POST['description']];

    if ($image_path) {
        $sql .= ", image = ?";
        $params[] = $image_path;
    }

    $sql .= " WHERE id = ?";
    $params[] = $_POST['id'];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Update category if provided
    if (isset($_POST['category'])) {
        // First delete existing category
        $stmt = $pdo->prepare("DELETE FROM item_categories WHERE item_id = ?");
        $stmt->execute([$_POST['id']]);

        // Insert new category
        $stmt = $pdo->prepare("INSERT INTO item_categories (item_id, category) VALUES (?, ?)");
        $stmt->execute([$_POST['id'], $_POST['category']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Listing updated successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in update_listing.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 