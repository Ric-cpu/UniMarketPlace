<?php
// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receiver_id'], $_POST['item_id'])) {
    $receiver_id = $_POST['receiver_id'];
    $item_id = $_POST['item_id'];

    // Retrieve conversation_id from POST data
    if (isset($_POST['conversation_id'])) {
        $conversation_id = intval($_POST['conversation_id']);
    } else {
        $conversation_id = null;
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

    // Get sender's (current user's) ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($sender_id);
    $stmt->fetch();
    $stmt->close();

    // Get receiver's username
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $stmt->bind_result($receiver_username);
    $stmt->fetch();
    $stmt->close();

    // Get item name
    $stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $stmt->bind_result($item_name);
    $stmt->fetch();
    $stmt->close();

    $conn->close();
} else {
    header("Location: Inbox.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reply to Message</title>
</head>
<body>
    <h1>Reply to Message</h1>
    <p><strong>To:</strong> <?php echo htmlspecialchars($receiver_username); ?></p>
    <p><strong>Item:</strong> <?php echo htmlspecialchars($item_name); ?></p>
    <form action="SendMessage.php" method="POST">
    <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($receiver_id); ?>">
    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
    <input type="hidden" name="conversation_id" value="<?php echo htmlspecialchars($conversation_id); ?>">
    <label for="message">Your Message:</label><br>
    <textarea id="message" name="message" rows="5" cols="50" required></textarea><br>
    <button type="submit">Send Reply</button>
</form>
    
</body>
</html>
