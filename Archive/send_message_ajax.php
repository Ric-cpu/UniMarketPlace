<?php
// Start the session and ensure the user is logged in
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['conversation_id'], $data['item_id'], $data['message'])) {
    $conversation_id = intval($data['conversation_id']);
    $item_id = intval($data['item_id']);
    $message_content = $data['message'];

    // Database connection
    $servername = "proj-mysql.uopnet.plymouth.ac.uk";
    $db_username = "comp2003_5";
    $db_password = "GcvN732+";
    $dbname = "comp2003_5";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }

    // Get sender's user ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($sender_id);
    $stmt->fetch();
    $stmt->close();

    // Get receiver's user ID from conversation
    $stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE conversation_id = ? LIMIT 1");
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $stmt->bind_result($sender_id_conv, $receiver_id_conv);
    $stmt->fetch();
    $stmt->close();

    if ($sender_id_conv == $sender_id) {
        $receiver_id = $receiver_id_conv;
    } else {
        $receiver_id = $sender_id_conv;
    }

    // Insert the message into the database
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, item_id, content, sent_at, conversation_id) VALUES (?, ?, ?, ?, NOW(), ?)");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    $stmt->bind_param("iiisi", $sender_id, $receiver_id, $item_id, $message_content, $conversation_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
}
?>
