<?php
// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

// Database connection
$servername = "proj-mysql.uopnet.plymouth.ac.uk";
$db_username = "comp2003_5";
$db_password = "GcvN732+";
$dbname = "comp2003_5";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - University of Plymouth Marketplace</title>
    <link rel="stylesheet" href="styles/ShopPage.css">
</head>
<body>

    <!-- Header -->
    <header id="main-header">
        <a href="Home.php" class="logo">
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

    <!-- Hero Section -->
    <section id="hero">
        <h1>Featured Products</h1>
    </section>

    <!-- Products Section -->
    <section id="products">
        <div class="product-container">
            <?php
            // Fetch items from database
            $sql = "SELECT items.*, users.username FROM items JOIN users ON items.user_id = users.id ORDER BY items.created_at DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // Output data of each item
                while($row = $result->fetch_assoc()) {
                    echo '<div class="product-card">';
                    echo '<img src="' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['name']) . '">';
                    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
                    echo '<p>Price: £' . htmlspecialchars($row['price']) . '</p>';
                    echo '<p>Seller: ' . htmlspecialchars($row['username']) . '</p>';
                    echo "<form action='MessageSeller.php' method='POST'>";
                    echo "<input type='hidden' name='item_id' value='" . htmlspecialchars($row['id']) . "'>";
                    echo "<input type='hidden' name='seller_id' value='" . htmlspecialchars($row['user_id']) . "'>";
                    echo "<button type='submit'>Buy</button>";
                    echo "</form>";
                    echo '</div>';
                }
            } else {
                echo '<p>No items available.</p>';
            }

            $conn->close();
            ?>
        </div>
    </section>

    <!-- Optional Footer -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> University of Plymouth Marketplace. All rights reserved.</p>
    </footer>

</body>
</html>
