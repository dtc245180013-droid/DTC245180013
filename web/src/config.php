<?php
session_start();
$host = getenv("DB_HOST") ?: "db";
$db = getenv("MYSQL_DATABASE") ?: "gym";
$user = getenv("MYSQL_USER") ?: "gym_app";
$pass = getenv("MYSQL_PASSWORD") ?: "GymAppP@ssw0rd!2026";
$charset = "utf8mb4";
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
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
