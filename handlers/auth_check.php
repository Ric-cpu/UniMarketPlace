<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Return user data as JSON if they are logged in
$userData = [
    'user_id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'first_name' => $_SESSION['first_name']
];

echo json_encode($userData);
?> 