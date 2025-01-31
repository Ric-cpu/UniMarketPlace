<?php
session_start();

// Database credentials
$servername = "proj-mysql.uopnet.plymouth.ac.uk";
$username = "comp2003_5";
$password = "GcvN732+";
$dbname = "comp2003_5";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$user = $_POST['username'];
$pass = $_POST['password'];

// Hash the password using SHA2
$hashed_pass = hash('sha256', $pass);

// Prepare and bind
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $user, $hashed_pass);

// Execute the statement
$stmt->execute();

// Store the result
$stmt->store_result();

// Check if a user exists with the provided credentials
if ($stmt->num_rows > 0) {
    // Login successful
    $_SESSION['username'] = $user;
    header("Location: Home.php");
} else {
    // Login failed
    echo "<script>alert('Invalid username or password'); window.location.href='Login.html';</script>";
}

// Close connections
$stmt->close();
$conn->close();
?>
