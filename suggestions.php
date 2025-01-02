<?php
// suggestions.php

header('Content-Type: application/json');

// Database credentials
$servername = "proj-mysql.uopnet.plymouth.ac.uk";
$db_username = "comp2003_5";
$db_password = "GcvN732+";
$dbname = "comp2003_5";

// Create a new database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);

// Check for a connection error
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

// Retrieve and sanitize the search term
$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term)) {
    echo json_encode([]);
    exit();
}

// Prepare the SQL statement
$stmt = $conn->prepare("SELECT name FROM items WHERE name LIKE CONCAT('%', ?, '%') LIMIT 10");
if ($stmt === false) {
    echo json_encode([]);
    exit();
}

$stmt->bind_param("s", $term);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row['name'];
}

echo json_encode($suggestions);

$stmt->close();
$conn->close();
?>
