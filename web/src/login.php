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
        $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
    } elseif ($user["status"] === "inactive") {
        file_put_contents("php://stderr", "LOGIN_FAILED ip=$ip user=$username reason=inactive\n");
        $error = "Tài khoản đang bị khóa hoặc ngưng hoạt động.";
    } elseif (!password_verify($password, $user["password_hash"])) {
        file_put_contents("php://stderr", "LOGIN_FAILED ip=$ip user=$username reason=wrong_password\n");
        $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
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
    <div class="col-md-5 col-lg-4">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white text-center py-4">
                <h4 class="mb-1 fw-bold text-uppercase"><i class="fas fa-dumbbell text-warning me-2"></i>ĐĂNG NHẬP</h4>
                <small class="text-secondary">Hệ thống Quản lý Phòng Gym & Fitness</small>
            </div>
            <div class="card-body p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="VD: admin hoặc hoivien01" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold text-uppercase"><i class="fas fa-sign-in-alt me-1"></i>Đăng Nhập</button>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-3 border-0 small text-muted">
                Dành cho Quản lý, Huấn luyện viên & Hội viên
            </div>
        </div>
    </div>
</div>
<?php require_once "footer.php"; ?>
