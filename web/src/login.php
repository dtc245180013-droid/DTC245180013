<?php
require_once "config.php";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    $ip = $_SERVER["REMOTE_ADDR"];
    if (!$user) {
        file_put_contents("php://stderr", "LOGIN_FAILED ip=$ip user=$username reason=unknown_user\n");
        $error = "Invalid credentials.";
    } elseif ($user["status"] === "inactive") {
        file_put_contents("php://stderr", "LOGIN_FAILED ip=$ip user=$username reason=inactive\n");
        $error = "Account is inactive.";
    } elseif (!password_verify($password, $user["password_hash"])) {
        file_put_contents("php://stderr", "LOGIN_FAILED ip=$ip user=$username reason=wrong_password\n");
        $error = "Invalid credentials.";
    } else {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["role"] = $user["role"];
        $_SESSION["username"] = $user["username"];
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
    <h1>Login</h1>
    <?php if (isset($error)) echo "<p style='color:red'>".htmlspecialchars($error)."</p>"; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
        Username: <input type="text" name="username" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
