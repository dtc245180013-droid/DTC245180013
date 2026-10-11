<?php
require_once "config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION["role"];
$role_names = [
    "manager" => "Quản Lý Hệ Thống",
    "trainer" => "Huấn Luyện Viên (PT)",
    "member" => "Hội Viên Thân Thiết"
];
?>
<?php require_once "header.php"; ?>
<div class="row mt-4 justify-content-center">
    <div class="col-md-8 text-center">
        <div class="card shadow-sm p-5 border-0">
            <h1 class="display-5 fw-bold text-primary mb-3"><i class="fas fa-user-circle me-2"></i>Xin chào, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            <p class="lead text-muted mb-4">Bạn đang đăng nhập với vai trò: <strong><span class="badge bg-success fs-6"><?php echo htmlspecialchars($role_names[$role] ?? $role); ?></span></strong></p>
            <hr class="my-4">
            
            <div class="d-flex justify-content-center gap-3">
            <?php if ($role === "manager"): ?>
                <a href='manager.php' class='btn btn-primary btn-lg px-4'><i class="fas fa-tasks me-2"></i>Vào Bảng Quản Trị</a>
            <?php elseif ($role === "trainer"): ?>
                <a href='trainer.php' class='btn btn-success btn-lg px-4'><i class="fas fa-calendar-alt me-2"></i>Xem Lịch Huấn Luyện</a>
            <?php elseif ($role === "member"): ?>
                <a href='member.php' class='btn btn-primary btn-lg px-4'><i class="fas fa-calendar-check me-2"></i>Xem Lớp & Đặt Chỗ Ngay</a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once "footer.php"; ?>
