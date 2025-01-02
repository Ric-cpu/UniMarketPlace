<?php
session_start();
?>
<!DOCTYPE html>

<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>University of Plymouth Marketplace</title>
    <link rel="stylesheet" href="styles/Home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

</head>
<body>

    <!-- Header Section -->
    <header id="main-header">
       <a href="#" class="logo"><img src="images/logo.png" alt="Marketplace Logo"></a>
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
                <li><a href="Home.php" class="active">Home</a></li>
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

    <!-- Hero Section -->
    <section id="hero">
        <h4>Welcome to StudentSwap</h4>
        <h1>Buy, Sell, and Connect</h1>
        <p>Right here on campus!</p>
        <a href="ShopPage.php" class="btn-primary">Shop Now</a>
    </section>

    <!-- Features Section -->
    <section id="features">
        <div class="feature-box">Feature 1</div>
        <div class="feature-box">Feature 2</div>
        <div class="feature-box">Feature 3</div>
        <div class="feature-box">Feature 4</div>
    </section>

    <script src="scripts/main.js"></script>
</body>
</html>
