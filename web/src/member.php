<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "member") {
    die("Access denied");
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
            } else {
                $pdo->rollBack();
                $_SESSION["error"] = "Class is full.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    } elseif ($_POST["action"] === "cancel") {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND member_id = ?");
        $stmt->execute([$_POST["booking_id"], $member_id]);
    }
    header("Location: member.php");
    exit;
}

$schedules = $pdo->query("SELECT * FROM schedules WHERE start_time > NOW()")->fetchAll();
$my_bookings = $pdo->prepare("SELECT b.id, s.class_name, s.start_time, b.status FROM bookings b JOIN schedules s ON b.schedule_id = s.id WHERE b.member_id = ?");
$my_bookings->execute([$member_id]);
$my_bookings = $my_bookings->fetchAll();
?>
<?php require_once "header.php"; ?>
<h2 class="mb-4"><i class="fas fa-calendar-alt text-info"></i> Member Dashboard - <?php echo htmlspecialchars($member["name"]); ?></h2>

<?php if (isset($_SESSION["error"])): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_SESSION["error"]); unset($_SESSION["error"]); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" id="memberTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="true">My Đặt lớpings</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab" aria-controls="classes" aria-selected="false">Available Classes</button>
  </li>
</ul>

<div class="tab-content" id="memberTabsContent">
  <!-- My Đặt lớpings Tab -->
  <div class="tab-pane fade show active" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list-ul"></i> My Đặt lớpings</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Class</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_bookings as $b): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($b["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, H:i', strtotime($b["start_time"]))); ?></td>
                                <td>
                                    <?php if ($b["status"] === "booked"): ?>
                                        <span class="badge bg-warning text-dark">Đặt lớped</span>
                                    <?php elseif ($b["status"] === "attended"): ?>
                                        <span class="badge bg-success">Attended</span>
                                    <?php elseif ($b["status"] === "cancelled"): ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($b["status"]); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b["status"] === "booked"): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($my_bookings) === 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No bookings yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Available Classes Tab -->
  <div class="tab-pane fade" id="classes" role="tabpanel" aria-labelledby="classes-tab">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Available Classes</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Class</th>
                                <th>Time</th>
                                <th>Capacity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($s["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, H:i', strtotime($s["start_time"]))); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s["capacity"]); ?></span></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                        <input type="hidden" name="action" value="book">
                                        <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($s["id"]); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-bookmark"></i> Đặt lớp</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($schedules) === 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No classes available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
<?php require_once "footer.php"; ?>
