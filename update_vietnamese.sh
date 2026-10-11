#!/bin/bash
cd /home/huyenxinh/gym/web/src

# 1. Update header.php
cat << 'EOF' > header.php
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
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
                        <span class="badge bg-primary ms-1"><?php echo htmlspecialchars($display_role); ?></span>
                    </span>
                    <a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container pb-5">
EOF

# 2. Update login.php
cat << 'EOF' > login.php
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
EOF

# 3. Update index.php
cat << 'EOF' > index.php
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
EOF

# 4. Update trainer.php
cat << 'EOF' > trainer.php
<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "trainer") {
    die("Từ chối truy cập");
}

$stmt = $pdo->prepare("SELECT id, name, specialty FROM trainers WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$trainer = $stmt->fetch();
$trainer_id = $trainer["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    verify_csrf();
    if ($_POST["action"] === "mark_attendance") {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'attended' WHERE id = ? AND schedule_id IN (SELECT id FROM schedules WHERE trainer_id = ?)");
        $stmt->execute([$_POST["booking_id"], $trainer_id]);
    }
    header("Location: trainer.php");
    exit;
}

$schedules = $pdo->prepare("SELECT * FROM schedules WHERE trainer_id = ? ORDER BY start_time ASC");
$schedules->execute([$trainer_id]);
$schedules = $schedules->fetchAll();
?>
<?php require_once "header.php"; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-stopwatch text-success me-2"></i>Lịch Huấn Luyện Của Tôi</h2>
        <span class="text-muted">HLV: <strong><?php echo htmlspecialchars($trainer['name']); ?></strong> | Chuyên môn: <span class="badge bg-info text-dark"><?php echo htmlspecialchars($trainer['specialty']); ?></span></span>
    </div>
</div>

<?php if (empty($schedules)): ?>
    <div class="alert alert-info shadow-sm"><i class="fas fa-info-circle me-1"></i>Hiện tại bạn chưa được phân công lịch lớp dạy nào.</div>
<?php else: ?>
<ul class="nav nav-tabs mb-4" id="trainerTabs" role="tablist">
    <?php foreach ($schedules as $index => $s): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                id="schedule-tab-<?php echo $s['id']; ?>" 
                data-bs-toggle="tab" 
                data-bs-target="#schedule-<?php echo $s['id']; ?>" 
                type="button" role="tab">
            <?php echo htmlspecialchars($s["class_name"]); ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="trainerTabsContent">
    <?php foreach ($schedules as $index => $s): ?>
    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
         id="schedule-<?php echo $s['id']; ?>" 
         role="tabpanel">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="fas fa-dumbbell text-warning me-2"></i><?php echo htmlspecialchars($s["class_name"]); ?></h5>
                    <small class="text-light opacity-75"><i class="far fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($s["start_time"])); ?> - <?php echo date('H:i', strtotime($s["end_time"])); ?> | Sức chứa: <?php echo $s['capacity']; ?> học viên</small>
                </div>
            </div>
            <div class="card-body p-0">
                <?php
                $bStmt = $pdo->prepare("SELECT b.id, m.name, m.phone, b.status FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.schedule_id = ?");
                $bStmt->execute([$s["id"]]);
                $bookings = $bStmt->fetchAll();
                ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Họ và tên học viên</th>
                                <th>Số điện thoại</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Điểm danh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($b["name"]); ?></td>
                                <td><?php echo htmlspecialchars($b["phone"] ?? "Chưa cập nhật"); ?></td>
                                <td>
                                    <?php if ($b["status"] === "attended"): ?>
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Đã tham gia</span>
                                    <?php elseif ($b["status"] === "booked"): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Chờ điểm danh</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($b["status"]); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($b["status"] === "booked"): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                        <input type="hidden" name="action" value="mark_attendance">
                                        <input type="hidden" name="booking_id" value="<?php echo $b["id"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-user-check me-1"></i>Điểm Danh</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="fas fa-check-double text-success"></i> Hoàn tất</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-user-friends me-1"></i>Chưa có học viên nào đăng ký lớp này.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once "footer.php"; ?>
EOF

# 5. Update member.php
cat << 'EOF' > member.php
<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "member") {
    die("Từ chối truy cập");
}

$stmt = $pdo->prepare("SELECT id, name FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$member = $stmt->fetch();
$member_id = $member["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    verify_csrf();
    if ($_POST["action"] === "book") {
        $schedule_id = $_POST["schedule_id"];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT capacity FROM schedules WHERE id = ? FOR UPDATE");
            $stmt->execute([$schedule_id]);
            $schedule = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bookings WHERE schedule_id = ? AND status IN ('booked', 'attended')");
            $stmt->execute([$schedule_id]);
            $count = $stmt->fetch()["c"];
            
            if ($count < $schedule["capacity"]) {
                $stmt = $pdo->prepare("INSERT INTO bookings (schedule_id, member_id, status) VALUES (?, ?, 'booked')");
                $stmt->execute([$schedule_id, $member_id]);
                $pdo->commit();
                $_SESSION["success"] = "Đặt lớp thành công!";
            } else {
                $pdo->rollBack();
                $_SESSION["error"] = "Lớp học đã đủ số lượng học viên tối đa.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION["error"] = "Có lỗi xảy ra khi đặt chỗ.";
        }
    } elseif ($_POST["action"] === "cancel") {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND member_id = ?");
        $stmt->execute([$_POST["booking_id"], $member_id]);
        $_SESSION["success"] = "Đã hủy lịch tập thành công.";
    }
    header("Location: member.php");
    exit;
}

$schedules = $pdo->query("SELECT s.*, t.name as trainer_name, t.specialty, (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = s.id AND b.status IN ('booked', 'attended')) as booked_count FROM schedules s JOIN trainers t ON s.trainer_id = t.id WHERE s.start_time > NOW() ORDER BY s.start_time ASC")->fetchAll();

$my_bookings = $pdo->prepare("SELECT b.id, s.class_name, s.start_time, s.end_time, t.name as trainer_name, b.status FROM bookings b JOIN schedules s ON b.schedule_id = s.id JOIN trainers t ON s.trainer_id = t.id WHERE b.member_id = ? ORDER BY s.start_time DESC");
$my_bookings->execute([$member_id]);
$my_bookings = $my_bookings->fetchAll();
?>
<?php require_once "header.php"; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-calendar-alt text-primary me-2"></i>Khu Vực Hội Viên</h2>
        <span class="text-muted">Hội viên: <strong><?php echo htmlspecialchars($member["name"]); ?></strong></span>
    </div>
</div>

<?php if (isset($_SESSION["error"])): ?>
    <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION["error"]); unset($_SESSION["error"]); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION["success"])): ?>
    <div class="alert alert-success shadow-sm"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION["success"]); unset($_SESSION["success"]); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="memberTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab"><i class="fas fa-history me-1"></i>Lịch Sử Đặt Lớp</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab"><i class="fas fa-calendar-plus me-1"></i>Các Lớp Đang Mở Đăng Ký</button>
  </li>
</ul>

<div class="tab-content" id="memberTabsContent">
  <!-- Tab 1: Bookings -->
  <div class="tab-pane fade show active" id="bookings" role="tabpanel">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-list-check me-2"></i>Lịch Lớp Đã Đăng Ký</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tên lớp học</th>
                            <th>Huấn luyện viên</th>
                            <th>Thời gian tập</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_bookings as $b): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo htmlspecialchars($b["class_name"]); ?></td>
                            <td><?php echo htmlspecialchars($b["trainer_name"]); ?></td>
                            <td><i class="far fa-clock me-1 text-muted"></i><?php echo date('d/m/Y H:i', strtotime($b["start_time"])); ?></td>
                            <td>
                                <?php if ($b["status"] === "booked"): ?>
                                    <span class="badge bg-primary">Đã đặt chỗ</span>
                                <?php elseif ($b["status"] === "attended"): ?>
                                    <span class="badge bg-success">Đã tham gia</span>
                                <?php elseif ($b["status"] === "cancelled"): ?>
                                    <span class="badge bg-secondary">Đã hủy</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($b["status"]); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($b["status"] === "booked"): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn hủy đặt chỗ lớp này?');"><i class="fas fa-times me-1"></i>Hủy Chỗ</button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($my_bookings)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-calendar-times me-1"></i>Bạn chưa đăng ký lớp tập nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>

  <!-- Tab 2: Available Classes -->
  <div class="tab-pane fade" id="classes" role="tabpanel">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-plus me-2"></i>Danh Sách Lớp Tập Đang Mở</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tên lớp học & Bộ môn</th>
                            <th>Huấn luyện viên phụ trách</th>
                            <th>Khung giờ học</th>
                            <th>Chỗ còn lại</th>
                            <th class="text-end">Đăng ký</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($s["class_name"]); ?></td>
                            <td><?php echo htmlspecialchars($s["trainer_name"]); ?> <small class="text-muted">(<?php echo htmlspecialchars($s["specialty"]); ?>)</small></td>
                            <td><i class="far fa-clock me-1 text-muted"></i><?php echo date('d/m/Y H:i', strtotime($s["start_time"])); ?> - <?php echo date('H:i', strtotime($s["end_time"])); ?></td>
                            <td>
                                <?php 
                                $slots_left = $s["capacity"] - ($s["booked_count"] ?? 0);
                                if ($slots_left <= 0): ?>
                                    <span class="badge bg-danger">Hết chỗ</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Còn <?php echo $slots_left; ?> / <?php echo $s["capacity"]; ?> chỗ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($slots_left > 0): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                    <input type="hidden" name="action" value="book">
                                    <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($s["id"]); ?>">
                                    <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-bookmark me-1"></i>Đặt Chỗ Ngay</button>
                                </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Đã Đầy Lớp</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($schedules)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-info-circle me-1"></i>Hiện không có lịch lớp học nào sắp diễn ra.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>
<?php require_once "footer.php"; ?>
EOF

# 6. Update manager.php labels in Vietnamese
python3 -c "
with open('manager.php', 'r', encoding='utf-8') as f:
    text = f.read()

# Replace tab names and headings with professional Vietnamese gym terms
replacements = {
    'Báo cáo (Reports)': 'Báo Cáo Hoạt Động',
    'Hội viên (Members & Renew)': 'Quản Lý Hội Viên & Gia Hạn',
    'Gói tập (Packages)': 'Gói Tập Luyện',
    'Huấn luyện viên (Trainers)': 'Đội Ngũ Huấn Luyện Viên',
    'Lịch tập (Schedules)': 'Lịch Học & Lớp Luyện Tập',
    'Total Revenue': 'Tổng Doanh Thu Phòng Gym',
    'New Members (30 days)': 'Hội Viên Mới (30 ngày qua)',
    'Avg Fill Rate': 'Tỷ Lệ Lấp Đầy Lớp Học',
    'Packages': 'Danh Sách Gói Tập',
    'Add Package': 'Thêm Gói Tập Mới',
    'Members': 'Danh Sách Hội Viên',
    'Add Member': 'Thêm Hội Viên Mới',
    'Trainers': 'Danh Sách Huấn Luyện Viên (PT)',
    'Add Trainer': 'Thêm Huấn Luyện Viên Mới',
    'Schedules': 'Lịch Trình Các Lớp Học',
    'Add Schedule': 'Xếp Lịch Lớp Mới',
    'Renew Package': 'Gia Hạn Gói & Thanh Toán',
    'Select Member': '-- Chọn Hội Viên --',
    'Select Package': '-- Chọn Gói Tập --',
    'Select Trainer': '-- Chọn Huấn Luyện Viên --',
    'Duration (days)': 'Thời hạn (ngày)',
    'Price ($)': 'Giá gói (VNĐ)',
    'Username': 'Tên đăng nhập',
    'Password': 'Mật khẩu',
    'Name': 'Họ và tên',
    'Phone': 'Số điện thoại',
    'Class Name': 'Tên lớp học',
    'Start Time': 'Thời gian bắt đầu',
    'End Time': 'Thời gian kết thúc',
    'Capacity': 'Sức chứa tối đa (người)'
}

for old, new in replacements.items():
    text = text.replace(old, new)

with open('manager.php', 'w', encoding='utf-8') as f:
    f.write(text)
print('Manager.php successfully updated with authentic Vietnamese terms!')
"

cd /home/huyenxinh/gym
docker compose build --no-cache web
docker compose up -d web
echo "ALL DONE"
