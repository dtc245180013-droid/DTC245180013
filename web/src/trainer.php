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
<!DOCTYPE html>
<html>
<head><title>Trainer Dashboard</title></head>
<body>
    <h1>Trainer Dashboard</h1>
    <a href="index.php">Back</a>
    <h2>My Schedules</h2>
    <?php foreach ($schedules as $s): ?>
        <h3><?php echo htmlspecialchars($s["class_name"]); ?> (<?php echo htmlspecialchars($s["start_time"]); ?> - <?php echo htmlspecialchars($s["end_time"]); ?>)</h3>
        <?php
        $bStmt = $pdo->prepare("SELECT b.id, m.name, b.status FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.schedule_id = ?");
        $bStmt->execute([$s["id"]]);
        $bookings = $bStmt->fetchAll();
        ?>
        <ul>
            <?php foreach ($bookings as $b): ?>
                <li>
                    <?php echo htmlspecialchars($b["name"]); ?> - Status: <?php echo htmlspecialchars($b["status"]); ?>
                    <?php if ($b["status"] === "booked"): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                            <input type="hidden" name="action" value="mark_attendance">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                            <button type="submit">Mark Attended</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</body>
</html>
