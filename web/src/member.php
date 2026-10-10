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
<!DOCTYPE html>
<html>
<head><title>Member Dashboard</title></head>
<body>
    <h1>Member Dashboard - <?php echo htmlspecialchars($member["name"]); ?></h1>
    <a href="index.php">Back</a>
    <?php if (isset($_SESSION["error"])) { echo "<p style='color:red'>" . htmlspecialchars($_SESSION["error"]) . "</p>"; unset($_SESSION["error"]); } ?>
    
    <h2>My Bookings</h2>
    <ul>
        <?php foreach ($my_bookings as $b): ?>
            <li>
                <?php echo htmlspecialchars($b["class_name"]); ?> at <?php echo htmlspecialchars($b["start_time"]); ?> - <?php echo htmlspecialchars($b["status"]); ?>
                <?php if ($b["status"] === "booked"): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                        <button type="submit">Cancel</button>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Available Classes</h2>
    <ul>
        <?php foreach ($schedules as $s): ?>
            <li>
                <?php echo htmlspecialchars($s["class_name"]); ?> (<?php echo htmlspecialchars($s["start_time"]); ?>) - Capacity: <?php echo htmlspecialchars($s["capacity"]); ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                    <input type="hidden" name="action" value="book">
                    <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($s["id"]); ?>">
                    <button type="submit">Book</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
