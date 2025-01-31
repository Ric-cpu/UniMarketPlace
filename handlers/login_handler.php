<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    try {
        // Debug: Print the email we're searching for
        error_log("Attempting login for email: " . $email);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Debug: Check if user was found
        error_log("User found: " . ($user ? 'Yes' : 'No'));

        if ($user && password_verify($password, $user['password'])) {
            // Debug: Check password verification
            error_log("Password verification: Success");

            if (!$user['is_verified']) {
                header("Location: ../login.html?error=" . urlencode("Please verify your email first."));
                exit();
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            
            // Handle remember me
            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }

            header("Location: ../dashboard.html");
            exit();
        } else {
            // Debug: Log password verification failure
            error_log("Password verification: Failed");
            header("Location: ../login.html?error=" . urlencode("Invalid email or password"));
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: ../login.html?error=" . urlencode("Login failed. Please try again."));
        exit();
    }
}
?> 