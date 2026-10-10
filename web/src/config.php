<?php
session_start();
$host = getenv("DB_HOST") ?: "127.0.0.1";
$db = getenv("DB_NAME") ?: "gym_db";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASS") ?: "";
$charset = "utf8mb4";
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // silently catch for now
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function verify_csrf() {
    if (!isset($_POST["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])) {
        die("CSRF token validation failed");
    }
}
?>