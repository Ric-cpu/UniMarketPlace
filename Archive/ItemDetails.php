<?php
// Start the session and ensure the user is logged in (optional)
session_start();

// Check if the item ID is provided
if (isset($_GET['id'])) {
    $item_id = intval($_GET['id']);

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

    // Prepare the SQL statement to fetch the item details
    $stmt = $conn->prepare("SELECT items.*, users.username FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the item exists
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
    } else {
        // If the item does not exist, redirect to Home.php with an error message
        echo "<script>alert('Item not found.'); window.location.href='Home.php';</script>";
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // If no item ID is provided, redirect to Home.php
    header("Location: Home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['name']); ?> - University of Plymouth Marketplace</title>
    <link rel="stylesheet" href="styles/ItemDetails.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    
</head>
<body>

    <!-- Header Section -->
    <header id="main-header">
        <a href="Home.php" class="logo"><img src="path-to-logo.png" alt="Marketplace Logo"></a>
        <nav class="navbar">
            <!-- Search Bar -->
            <form action="Search.php" method="get" class="search-bar">
                <input type="text" name="query" placeholder="Search products" required />
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>

            <!-- Navigation Links -->
            <ul class="nav-links">
                <?php if(isset($_SESSION['username'])): ?>
                    <li><a href="Profile.php">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="Login.html">Login</a></li>
                <?php endif; ?>
                <li><a href="Home.php">Home</a></li>
                <li><a href="ShopPage.php">Shop</a></li>
                <li><a href="Inbox.php">Messages</a></li>
                <li><a href="Contact.php">Contact</a></li>
                <li><a href="YourItems.php">Your Items</a></li>
            </ul>

            <!-- Sell Button -->
            <div class="sell-button-container">
                <a href="SellPage.php" class="sell-button">Sell</a>
            </div>
        </nav>
    </header>

    <!-- Item Details Section -->
    <section id="item-details" class="item-details-container">
        <div class="item-image">
            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
        </div>
        <div class="item-info">
            <h2><?php echo htmlspecialchars($item['name']); ?></h2>
            <p><strong>Price:</strong> £<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
            <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
            <p><strong>Seller:</strong> <?php echo htmlspecialchars($item['username']); ?></p>
            <div class="contact-seller">
                <a href="MessageSeller.php?seller_id=<?php echo htmlspecialchars($item['user_id']); ?>&item_id=<?php echo htmlspecialchars($item['id']); ?>">Contact Seller</a>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer>
        <p>&copy; <?php echo date("Y"); ?> University of Plymouth Marketplace. All rights reserved.</p>
    </footer>

</body>
</html>
