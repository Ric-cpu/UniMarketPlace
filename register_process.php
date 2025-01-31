<?php
session_start();
require_once 'db_connect.php';

function generateVerificationCode() {
    return sprintf("%06d", mt_rand(0, 999999));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $graduation_year = filter_var($_POST['graduation_year'], FILTER_SANITIZE_NUMBER_INT);

    // Validate student email
    if (!preg_match('/\.ac\.uk$/', $email)) {
        $_SESSION['error'] = "Please use a valid academic email ending with .ac.uk";
        header("Location: register.html");
        exit();
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email already registered";
            header("Location: register.html");
            exit();
        }

        // Generate verification code
        $verification_code = generateVerificationCode();
        $verification_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user with unverified status
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, graduation_year, verification_code, verification_expires, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$first_name, $last_name, $email, $hashed_password, $graduation_year, $verification_code, $verification_expires]);

        // Send verification email
        $to = $email;
        $subject = "Verify your Student Marketplace account";
        $message = "Hi $first_name,\n\n";
        $message .= "Your verification code is: $verification_code\n";
        $message .= "This code will expire in 1 hour.";
        $headers = "From: noreply@studentmarketplace.com";

        mail($to, $subject, $message, $headers);

        $_SESSION['message'] = "Registration successful! Please check your email for verification code.";
        header("Location: verify.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: register.html");
        exit();
    }
}
?> 