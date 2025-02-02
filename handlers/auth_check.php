<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]);
    } else {
        header('Location: ../index.html');
        exit();
    }
} catch (Exception $e) {
    header('Location: ../index.html');
    exit();
}
?> 