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
        // Debug logging at the start
        error_log("Received POST data: " . print_r($_POST, true));
        
        $pdo->beginTransaction();
        
        $user_id = $_SESSION['user_id'];
        
        if (empty($_POST['name']) || empty($_POST['price']) || empty($_POST['description']) || empty($_POST['category'])) {
            throw new Exception('Missing required fields. Category: ' . ($_POST['category'] ?? 'not set'));
        }
        
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $description = $_POST['description'];
        $category = trim($_POST['category']); // Add trim to remove any whitespace

        // Debug category validation
        error_log("Validating category: " . $category);
        
        // Validate category exists
        $stmt = $pdo->prepare("SELECT category FROM categories WHERE category = ?");
        $stmt->execute([$category]);
        if (!$stmt->fetch()) {
            error_log("Category validation failed for: " . $category);
            throw new Exception('Invalid category: ' . $category);
        }
        error_log("Category validated successfully: " . $category);

        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Use relative path from the handler directory
            $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            
            // Create uploads directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!@mkdir($upload_dir, 0777, true)) {
                    throw new Exception('Failed to create directory: ' . $upload_dir);
                }
            }

            // Generate unique filename
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $full_path = $upload_dir . $new_filename;
            
            // Store relative path in database
            $image_path = 'uploads/' . $new_filename;

            // Try to move the uploaded file
            if (!@move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                $error = error_get_last();
                throw new Exception('Move failed: ' . ($error['message'] ?? 'Unknown error'));
            }

            // Check if file exists after move
            if (!file_exists($full_path)) {
                throw new Exception('File not found after upload: ' . $full_path);
            }
        }

        // Insert into items table
        $stmt = $pdo->prepare("
            INSERT INTO items (
                user_id, 
                name, 
                price, 
                description, 
                image,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        if (!$stmt->execute([
            $user_id,
            $name,
            $price,
            $description,
            $image_path
        ])) {
            throw new Exception('Database insert failed');
        }

        $item_id = $pdo->lastInsertId();
        
        // After getting item_id, log it
        error_log("Item inserted with ID: " . $item_id);
        
        // Insert category
        $stmt = $pdo->prepare("
            INSERT INTO item_categories (
                item_id,
                category
            ) VALUES (?, ?)
        ");

        error_log("Attempting to insert category: {$category} for item_id: {$item_id}");
        if (!$stmt->execute([$item_id, $category])) {
            $error = $stmt->errorInfo();
            error_log("Category insert failed: " . print_r($error, true));
            throw new Exception('Failed to insert category: ' . ($error[2] ?? 'Unknown error'));
        }
        error_log("Category inserted successfully");

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Listing created successfully',
            'debug' => [
                'category' => $category,
                'item_id' => $item_id,
                'post_data' => $_POST
            ],
            'redirect' => '../my-listings.html' // Fixed redirect path
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Error in create_listing_handler: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'debug' => [
                'post_data' => $_POST,
                'category' => $category ?? null,
                'files' => isset($_FILES['image']) ? [
                    'name' => $_FILES['image']['name'],
                    'tmp_name' => $_FILES['image']['tmp_name'],
                    'error' => $_FILES['image']['error'],
                    'size' => $_FILES['image']['size']
                ] : null
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?> 