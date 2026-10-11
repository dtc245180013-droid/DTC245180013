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
