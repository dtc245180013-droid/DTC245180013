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
<?php require_once "header.php"; ?>
<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white text-center py-3">
                <h4 class="mb-0"><i class="fas fa-dumbbell text-warning"></i> Gym Login</h4>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt"></i> Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once "footer.php"; ?>
