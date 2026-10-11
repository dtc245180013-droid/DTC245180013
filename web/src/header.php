<?php
// header.php
$role_names = [
    "manager" => "Quản Lý",
    "trainer" => "Huấn Luyện Viên",
    "member" => "Hội Viên"
];
$display_role = isset($_SESSION["role"]) ? ($role_names[$_SESSION["role"]] ?? $_SESSION["role"]) : "";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>Hệ Thống Quản Lý Phòng Gym & Thể Hình</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="custom.css">
</head>
<body>
    <?php if (isset($_SESSION["user_id"])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-dumbbell text-warning me-2"></i>GYM FITNESS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Trang chủ</a></li>
                    <?php if (isset($_SESSION["role"])): ?>
                        <?php if ($_SESSION["role"] === "manager"): ?>
                            <li class="nav-item"><a class="nav-link" href="manager.php"><i class="fas fa-tasks me-1"></i>Quản trị phòng tập</a></li>
                        <?php elseif ($_SESSION["role"] === "trainer"): ?>
                            <li class="nav-item"><a class="nav-link" href="trainer.php"><i class="fas fa-calendar-alt me-1"></i>Lịch huấn luyện</a></li>
                        <?php elseif ($_SESSION["role"] === "member"): ?>
                            <li class="nav-item"><a class="nav-link" href="member.php"><i class="fas fa-user-check me-1"></i>Đặt lớp & Lịch tập</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3 text-light small">
                        Xin chào, <strong><?php echo htmlspecialchars($_SESSION["username"] ?? ""); ?></strong> 
                        <span class="role-badge ms-1"><?php echo htmlspecialchars($display_role); ?></span>
                    </span>
                    <a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container pb-5">
