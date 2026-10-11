<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "trainer") {
    die("Access denied");
}

$stmt = $pdo->prepare("SELECT id FROM trainers WHERE user_id = ?");
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

$schedules = $pdo->prepare("SELECT * FROM schedules WHERE trainer_id = ?");
$schedules->execute([$trainer_id]);
$schedules = $schedules->fetchAll();
?>
<?php require_once "header.php"; ?>
<h2 class="mb-4"><i class="fas fa-stopwatch text-success"></i> Trainer Dashboard</h2>

<ul class="nav nav-tabs mb-4" id="trainerTabs" role="tablist">
    <?php foreach ($schedules as $index => $s): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                id="schedule-tab-<?php echo $s['id']; ?>" 
                data-bs-toggle="tab" 
                data-bs-target="#schedule-<?php echo $s['id']; ?>" 
                type="button" role="tab" 
                aria-controls="schedule-<?php echo $s['id']; ?>" 
                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
            <?php echo htmlspecialchars($s["class_name"]); ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="trainerTabsContent">
    <?php foreach ($schedules as $index => $s): ?>
    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
         id="schedule-<?php echo $s['id']; ?>" 
         role="tabpanel" 
         aria-labelledby="schedule-tab-<?php echo $s['id']; ?>">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($s["class_name"]); ?></h5>
                        <small><i class="far fa-clock"></i> <?php echo htmlspecialchars($s["start_time"]); ?> - <?php echo htmlspecialchars($s["end_time"]); ?></small>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $bStmt = $pdo->prepare("SELECT b.id, m.name, b.status FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.schedule_id = ?");
                        $bStmt->execute([$s["id"]]);
                        $bookings = $bStmt->fetchAll();
                        ?>
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Member</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td class="ps-3"><?php echo htmlspecialchars($b["name"]); ?></td>
                                    <td>
                                        <?php if ($b["status"] === "booked"): ?>
                                            <span class="badge bg-warning text-dark">Booked</span>
                                        <?php elseif ($b["status"] === "attended"): ?>
                                            <span class="badge bg-success">Attended</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($b["status"]); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b["status"] === "booked"): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                                <input type="hidden" name="action" value="mark_attendance">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Mark Attended</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($bookings) === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No bookings yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require_once "footer.php"; ?>
