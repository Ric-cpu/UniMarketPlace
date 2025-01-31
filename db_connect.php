<?php
$host = 'proj-mysql.uopnet.plymouth.ac.uk';
$dbname = 'comp2003_5';
$username = 'comp2003_5';
$password = 'GcvN732+';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 