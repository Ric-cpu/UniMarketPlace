<?php
// Start the session and ensure the user is logged in
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if (isset($_GET['conversation_id'])) {
    $conversation_id = intval($_GET['conversation_id']);

    // Database connection
    $servername = "proj-mysql.uopnet.plymouth.ac.uk";
    $db_username = "comp2003_5";
    $db_password = "GcvN732+";
    $dbname = "comp2003_5";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    // Get current user's ID
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->bind_result($current_user_id);
    $stmt->fetch();
    $stmt->close();

    // Fetch messages for the conversation
    $stmt = $conn->prepare("SELECT sender_id, content, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC");
    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'sender_id' => $row['sender_id'],
            'content' => htmlspecialchars($row['content']),
            'sent_at' => $row['sent_at']
        ];
    }

    echo json_encode(['messages' => $messages]);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid parameters']);
}
?>
