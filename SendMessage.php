<?php
// Enable error reporting for debugging purposes (remove or comment out in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session and ensure the user is logged in
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

// Check if the form was submitted via POST method and required data is present
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['receiver_id'], $_POST['item_id'], $_POST['message'])) {
    // Retrieve data from POST request
    $receiver_id = intval($_POST['receiver_id']);
    $item_id = intval($_POST['item_id']);
    $message_content = $_POST['message'];

    // Check if conversation_id is provided (for replies)
    if (isset($_POST['conversation_id']) && !empty($_POST['conversation_id'])) {
        // This is a reply; use the existing conversation_id
        $conversation_id = intval($_POST['conversation_id']);
    } else {
        // This is a new conversation; conversation_id will be set to the message's ID after insertion
        $conversation_id = null;
    }

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

    // Get sender's user ID (current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($sender_id);
    $stmt->fetch();
    $stmt->close();

    // Prevent users from messaging themselves
    if ($sender_id == $receiver_id) {
        die("You cannot send a message to yourself.");
    }

    // Insert the message into the database
    if ($conversation_id !== null) {
        // Existing conversation
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, item_id, content, sent_at, conversation_id) VALUES (?, ?, ?, ?, NOW(), ?)");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("iiisi", $sender_id, $receiver_id, $item_id, $message_content, $conversation_id);
    } else {
        // New conversation, omit conversation_id
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, item_id, content, sent_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("iiis", $sender_id, $receiver_id, $item_id, $message_content);
    }

    if ($stmt->execute()) {
        // If this is a new conversation, update the conversation_id to the message's ID
        if ($conversation_id === null) {
            $conversation_id = $stmt->insert_id;
            // Update the message with the conversation_id
            $update_stmt = $conn->prepare("UPDATE messages SET conversation_id = ? WHERE id = ?");
            if ($update_stmt === false) {
                die("Error preparing update statement: " . $conn->error);
            }
            $update_stmt->bind_param("ii", $conversation_id, $conversation_id);
            if (!$update_stmt->execute()) {
                die("Error updating conversation_id: " . $update_stmt->error);
            }
            $update_stmt->close();
        }
        echo "<script>alert('Message sent successfully!'); window.location.href='Inbox.php';</script>";
    } else {
        die("Error sending message: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    // If required POST data is missing, redirect to the Inbox
    header("Location: Inbox.php");
    exit();
}
?>
