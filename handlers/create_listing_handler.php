<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $user_id = $_SESSION['user_id'];
        
        if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['description']) || empty($_POST['category'])) {
            throw new Exception('Missing required fields');
        }
        
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        $category = trim($_POST['category']);

        // Handle multiple image uploads
        $image_paths = [];
        if (isset($_FILES['image']) && is_array($_FILES['image']['name'])) {
            $files = $_FILES['image'];
            $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Process each uploaded file
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $files['tmp_name'][$i];
                    $name_parts = explode('.', $files['name'][$i]);
                    $extension = strtolower(end($name_parts));
                    $new_filename = uniqid() . '.' . $extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $image_paths[] = 'uploads/' . $new_filename;
                    }
                }
            }
        }

        if (empty($image_paths)) {
            throw new Exception('No valid images uploaded');
        }

        // Insert main item record
        $stmt = $pdo->prepare("
            INSERT INTO items (user_id, name, price, description, image, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $name,
            $price,
            $description,
            $image_paths[0] // First image becomes the main image
        ]);
        
        $item_id = $pdo->lastInsertId();

        // Insert category into item_categories table
        $stmt = $pdo->prepare("
            INSERT INTO item_categories (item_id, category)
            VALUES (?, ?)
        ");
        $stmt->execute([$item_id, $category]);

        // Insert additional images
        if (count($image_paths) > 1) {
            $stmt = $pdo->prepare("
                INSERT INTO item_images (item_id, image_path, display_order)
                VALUES (?, ?, ?)
            ");
            
            // Start from index 1 since index 0 is already the main image
            for ($i = 1; $i < count($image_paths); $i++) {
                $stmt->execute([$item_id, $image_paths[$i], $i]);
            }
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Listing created successfully',
            'redirect' => 'my-listings.html'
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Error in create_listing_handler: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>