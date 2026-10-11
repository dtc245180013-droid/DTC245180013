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
