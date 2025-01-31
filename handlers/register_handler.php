<?php
session_start();
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input
        $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $graduation_year = filter_var($_POST['graduation_year'], FILTER_SANITIZE_NUMBER_INT);

        // Validate student email
        if (!preg_match('/\.ac\.uk$/', $email)) {
            throw new Exception('Please use a valid academic email ending with .ac.uk');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email already registered');
        }

        // Generate verification code
        $verification_code = sprintf("%06d", mt_rand(0, 999999));
        $verification_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name, 
                last_name, 
                email, 
                password, 
                graduation_year, 
                verification_code, 
                verification_expires, 
                is_verified, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");

        $stmt->execute([
            $first_name,
            $last_name,
            $email,
            $hashed_password,
            $graduation_year,
            $verification_code,
            $verification_expires
        ]);

        // Send verification email
        $to = $email;
        $subject = "Verify your Student Marketplace account";
        $message = "Hi $first_name,\n\n";
        $message .= "Thank you for registering with Student Marketplace.\n";
        $message .= "Your verification code is: $verification_code\n\n";
        $message .= "This code will expire in 1 hour.\n\n";
        $message .= "Best regards,\nStudent Marketplace Team";
        
        // Email headers
        $headers = "From: noreply@studentmarketplace.com\r\n";
        $headers .= "Reply-To: noreply@studentmarketplace.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send email
        if(!mail($to, $subject, $message, $headers)) {
            throw new Exception('Failed to send verification email. Please try again.');
        }

        // Store email in session for verification page
        $_SESSION['verification_email'] = $email;

        // Redirect to verification page
        header("Location: ../verify.html?email=" . urlencode($email));
        exit();

    } catch (Exception $e) {
        header("Location: ../register.html?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../register.html");
    exit();
}
?> 