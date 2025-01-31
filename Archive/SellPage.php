




<?php
// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

// Handle form submission when the form is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database credentials
    $servername = "proj-mysql.uopnet.plymouth.ac.uk";
    $db_username = "comp2003_5";
    $db_password = "GcvN732+";
    $dbname = "comp2003_5";

    // Create a new database connection
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    // Check for a connection error
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the current user's ID from the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Retrieve form data
    $item_name = $_POST['item-name'];
    $item_price = $_POST['item-price'];
    $item_description = $_POST['item-description'];

    // Handle the uploaded image
    if ($_FILES['item-image']['error'] !== UPLOAD_ERR_OK) {
        // Handle the file upload error
        $error_code = $_FILES['item-image']['error'];
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = 'A PHP extension stopped the file upload.';
                break;
            default:
                $message = 'Unknown upload error.';
                break;
        }
        echo "<script>alert('Error uploading file: $message'); window.location.href='SellPage.php';</script>";
    } else {
        // Validate the image file
        $tmp_file = $_FILES['item-image']['tmp_name'];
        $check = getimagesize($tmp_file);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "<script>alert('File is not an image.'); window.location.href='SellPage.php';</script>";
            $uploadOk = 0;
        }

        // Check file size (limit to 5MB)
        if ($_FILES["item-image"]["size"] > 5000000) {
            echo "<script>alert('Sorry, your file is too large.'); window.location.href='SellPage.php';</script>";
            $uploadOk = 0;
        }

        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($_FILES["item-image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        if (!in_array($imageFileType, $allowed_types)) {
            echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.'); window.location.href='SellPage.php';</script>";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            // Error message already shown
        } else {
            // Generate a unique filename for the uploaded file
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $unique_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $unique_filename;

            // Move the uploaded file to the target directory
            if (move_uploaded_file($tmp_file, $target_file)) {
                // Insert item into database
                $stmt = $conn->prepare("INSERT INTO items (user_id, name, price, description, image) VALUES (?, ?, ?, ?, ?)");
                if ($stmt === false) {
                    die("Error preparing statement: " . $conn->error);
                }
                $stmt->bind_param("isdss", $user_id, $item_name, $item_price, $item_description, $target_file);

                if ($stmt->execute()) {
                    echo "<script>alert('Item uploaded successfully!'); window.location.href='YourItems.php';</script>";
                } else {
                    echo "<script>alert('Error uploading item to the database.'); window.location.href='SellPage.php';</script>";
                }

                $stmt->close();
            } else {
                echo "<script>alert('Sorry, there was an error uploading your file.'); window.location.href='SellPage.php';</script>";
            }
        }
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell an Item - University of Plymouth Marketplace</title>
    <link rel="stylesheet" href="styles/SellPage.css">
</head>
<body>

    <!-- Header -->
    <header id="main-header">
        <a href="Home.php" class="logo">
            <img src="path-to-logo.png" alt="University of Plymouth Marketplace">
        </a>
        <nav class="navbar">
            <a href="Home.php" class="back-button">Back</a>
        </nav>
    </header>

    <!-- Sell Form Section -->
    <main>
        <h1>Sell an Item</h1>
        <form action="SellPage.php" method="POST" enctype="multipart/form-data" id="sell-form">
            <label for="item-image">Item Image:</label>
            <input type="file" id="item-image" name="item-image" accept="image/*" required>

            <label for="item-name">Item Name:</label>
            <input type="text" id="item-name" name="item-name" placeholder="Enter item name" required>

            <label for="item-price">Price:</label>
            <input type="number" id="item-price" name="item-price" placeholder="Enter price" step="0.01" required>

            <label for="item-description">Description:</label>
            <textarea id="item-description" name="item-description" rows="4" placeholder="Enter item description" required></textarea>

            <button type="submit" class="submit-button">Upload Item</button>
        </form>
    </main>

</body>
</html>
