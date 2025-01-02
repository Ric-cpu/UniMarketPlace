<?php
// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

// Handle item deletion when the delete button is pressed
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
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
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Get the item ID from the POST data
    $item_id = intval($_POST['item_id']);

    // Delete related messages
    $stmt = $conn->prepare("DELETE FROM messages WHERE item_id = ?");
    if ($stmt === false) {
        die("Error preparing delete statement for messages: " . $conn->error);
    }
    $stmt->bind_param("i", $item_id);
    if (!$stmt->execute()) {
        echo "<script>alert('Error deleting related messages.'); window.location.href='YourItems.php';</script>";
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Delete the item if it belongs to the current user
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
    if ($stmt === false) {
        die("Error preparing delete statement for items: " . $conn->error);
    }
    $stmt->bind_param("ii", $item_id, $user_id);
    if ($stmt->execute()) {
        echo "<script>alert('Item deleted successfully.'); window.location.href='YourItems.php';</script>";
    } else {
        echo "<script>alert('Error deleting item.'); window.location.href='YourItems.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Items - University of Plymouth Marketplace</title>
    <link rel="stylesheet" href="styles/YourItems.css">
</head>
<body>

    <!-- Header -->
    <header id="main-header">
        <a href="Home.html" class="logo">
            <img src="path-to-logo.png" alt="University of Plymouth Marketplace">
        </a>
        <nav class="navbar">
        <ul class="nav-links">
    <li><a href="Home.php">Home</a></li>
    <li><a href="ShopPage.php">Shop</a></li>
    <li><a href="Inbox.php">Messages</a></li>
    <li><a href="Contact.php">Contact</a></li>
    <li><a href="YourItems.php">Your Items</a></li>
    <?php if(isset($_SESSION['username'])): ?>
        <li><a href="SellPage.php" class="sell-button">Sell</a></li>
        <li><a href="Profile.php">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
        <li><a href="logout.php">Logout</a></li>
    <?php else: ?>
        <li><a href="Login.html">Login</a></li>
    <?php endif; ?>
</ul>
        </nav>
    </header>

    <!-- Your Items Section -->
    <section id="your-items">
        <h1>Your Items</h1>
        <div class="items-list">
            <?php
            // Database credentials
            $servername = "proj-mysql.uopnet.plymouth.ac.uk";
            $db_username = "comp2003_5";
            $db_password = "GcvN732+";
            $dbname = "comp2003_5";

            // Create connection
            $conn = new mysqli($servername, $db_username, $db_password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Get current user's ID
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $stmt->close();

            // Fetch user's items
            $stmt = $conn->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="item-card">';
                    echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                    echo '<p>Price: £' . htmlspecialchars($row['price']) . '</p>';
                    echo '<p>Description: ' . htmlspecialchars($row['description']) . '</p>';
                    echo '<form method="POST" action="YourItems.php">';
                    echo '<input type="hidden" name="item_id" value="' . htmlspecialchars($row['id']) . '">';
                    echo '<button type="submit" name="edit" class="edit-button">Edit</button>';
                    echo '<button type="submit" name="delete" class="delete-button" onclick="return confirm(\'Are you sure you want to delete this item?\')">Delete</button>';
                    echo '</form>';
                    echo '</div>';
                }
            } else {
                echo '<div class="empty-state">';
                echo '<p>No items listed yet. Click "Sell" to add your first item!</p>';
                echo '</div>';
            }

            $stmt->close();
            $conn->close();
            ?>
        </div>
    </section>

</body>
</html>