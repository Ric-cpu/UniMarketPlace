<?php
// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['item_id']) && isset($_POST['seller_id'])) {
    $item_id = $_POST['item_id'];
    $seller_id = $_POST['seller_id'];

    // Database connection
    $servername = "proj-mysql.uopnet.plymouth.ac.uk";
    $db_username = "comp2003_5";
    $db_password = "GcvN732+";
    $dbname = "comp2003_5";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get buyer's user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($buyer_id);
    $stmt->fetch();
    $stmt->close();

    // Prevent buyers from messaging themselves
    if ($buyer_id == $seller_id) {
        die("You cannot buy your own item.");
    }

    // Get item details
    $stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->bind_result($item_name);
    $stmt->fetch();
    $stmt->close();

    $conn->close();
} else {
    header("Location: Shop.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="styles/MessageSeller.css">
    <meta charset="UTF-8">
    <title>Message Seller</title>
</head>
<body>
    <h1>Contact Seller</h1>
    <h2>Item: <?php echo htmlspecialchars($item_name); ?></h2>
    <form action="SendMessage.php" method="POST">
    <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($seller_id); ?>">
    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
    <label for="message">Your Message:</label><br>
    <textarea id="message" name="message" rows="5" cols="50" required></textarea><br>
    <button type="submit">Send Message</button>
</form>
</body>
</html>
