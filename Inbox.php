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

// Get user's ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$stmt->bind_result($user_id);
$stmt->fetch();
$stmt->close();

// Fetch conversations where the user is a participant
$stmt = $conn->prepare("
    SELECT m.conversation_id, m.item_id, u.username AS other_user, i.name AS item_name, MAX(m.sent_at) AS last_message_time
    FROM messages m
    JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id = u.id ELSE m.sender_id = u.id END)
    JOIN items i ON m.item_id = i.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY m.conversation_id
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Inbox</title>
    <link rel="stylesheet" href="styles/Inbox.css">

</head>
<body>
    <h1>Your Conversations</h1>
    <?php
    if ($result->num_rows > 0) {
        while ($conversation = $result->fetch_assoc()) {
            echo "<div class='conversation'>";
            echo "<p><strong>Conversation with:</strong> " . htmlspecialchars($conversation['other_user']) . "</p>";
            echo "<p><strong>Item:</strong> " . htmlspecialchars($conversation['item_name']) . "</p>";
            echo "<p><strong>Last Message:</strong> " . htmlspecialchars($conversation['last_message_time']) . "</p>";
            echo "<a href='Chat.php?conversation_id=" . htmlspecialchars($conversation['conversation_id']) . "&item_id=" . htmlspecialchars($conversation['item_id']) . "'>Open Chat</a>";
            echo "</div><hr>";
        }
    } else {
        echo "<p>No conversations.</p>";
    }
    ?>
    <div class="homeBTN">
        <a href="Home.php">Home</a>
    </div>
</body>
</html>