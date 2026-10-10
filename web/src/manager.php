<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "manager") {
    die("Access denied");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    verify_csrf();
    
    // Package CRUD
    if ($_POST["action"] === "add_package") {
        $stmt = $pdo->prepare("INSERT INTO packages (name, duration_days, price) VALUES (?, ?, ?)");
        $stmt->execute([$_POST["name"], $_POST["duration_days"], $_POST["price"]]);
    } elseif ($_POST["action"] === "delete_package") {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    }
    
    // Member CRUD (and user)
    elseif ($_POST["action"] === "add_member") {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'member')");
            $hash = password_hash($_POST["password"], PASSWORD_BCRYPT);
            $stmt->execute([$_POST["username"], $hash]);
            $user_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO members (user_id, name, phone) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $_POST["name"], $_POST["phone"]]);
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); }
    } elseif ($_POST["action"] === "delete_member") {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM members WHERE id = ?)");
        $stmt->execute([$_POST["id"]]);
    }
    
    // Trainer CRUD
    elseif ($_POST["action"] === "add_trainer") {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'trainer')");
            $hash = password_hash($_POST["password"], PASSWORD_BCRYPT);
            $stmt->execute([$_POST["username"], $hash]);
            $user_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO trainers (user_id, name, specialty) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $_POST["name"], $_POST["specialty"]]);
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); }
    }
    
    // Schedule CRUD
    elseif ($_POST["action"] === "add_schedule") {
        $stmt = $pdo->prepare("INSERT INTO schedules (trainer_id, class_name, start_time, end_time, capacity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST["trainer_id"], $_POST["class_name"], $_POST["start_time"], $_POST["end_time"], $_POST["capacity"]]);
    }
    
    // Renew
    elseif ($_POST["action"] === "renew_package") {
        $member_id = $_POST["member_id"];
        $package_id = $_POST["package_id"];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT duration_days FROM packages WHERE id = ?");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch();
            $stmt = $pdo->prepare("SELECT end_date FROM memberships WHERE member_id = ? ORDER BY end_date DESC LIMIT 1");
            $stmt->execute([$member_id]);
            $last_membership = $stmt->fetch();
            $start_date = ($last_membership && $last_membership["end_date"] > date("Y-m-d")) ? $last_membership["end_date"] : date("Y-m-d");
            $end_date = date("Y-m-d", strtotime($start_date . " + " . $package["duration_days"] . " days"));
            $stmt = $pdo->prepare("INSERT INTO memberships (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$member_id, $package_id, $start_date, $end_date]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: manager.php");
    exit;
}

$members = $pdo->query("SELECT * FROM members")->fetchAll();
$packages = $pdo->query("SELECT * FROM packages")->fetchAll();
$trainers = $pdo->query("SELECT * FROM trainers")->fetchAll();

// Reports
$revenue = $pdo->query("SELECT SUM(p.price) as rev FROM memberships m JOIN packages p ON m.package_id = p.id")->fetch()["rev"];
$new_members = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='member' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch()["c"];
$fill_rate = $pdo->query("SELECT (SELECT COUNT(*) FROM bookings) / NULLIF(SUM(capacity), 0) as rate FROM schedules")->fetch()["rate"];
?>
<!DOCTYPE html>
<html>
<head><title>Manager Dashboard</title></head>
<body>
    <h1>Manager Dashboard</h1>
    <a href="index.php">Back</a>
    
    <h2>Reports</h2>
    <p>Total Revenue: $<?php echo number_format($revenue ?? 0, 2); ?></p>
    <p>New Members (30 days): <?php echo $new_members; ?></p>
    <p>Average Schedule Fill Rate: <?php echo number_format(($fill_rate ?? 0) * 100, 2); ?>%</p>
    
    <h2>Packages</h2>
    <ul>
    <?php foreach ($packages as $p): ?>
        <li>
            <?php echo htmlspecialchars($p["name"]); ?> - $<?php echo $p["price"]; ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                <input type="hidden" name="action" value="delete_package">
                <input type="hidden" name="id" value="<?php echo $p["id"]; ?>">
                <button type="submit">Del</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
        <input type="hidden" name="action" value="add_package">
        Name: <input type="text" name="name" required>
        Days: <input type="number" name="duration_days" required>
        Price: <input type="number" step="0.01" name="price" required>
        <button type="submit">Add Package</button>
    </form>
    
    <h2>Members</h2>
    <ul>
    <?php foreach ($members as $m): ?>
        <li>
            <?php echo htmlspecialchars($m["name"]); ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                <input type="hidden" name="action" value="delete_member">
                <input type="hidden" name="id" value="<?php echo $m["id"]; ?>">
                <button type="submit">Del</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
        <input type="hidden" name="action" value="add_member">
        Username: <input type="text" name="username" required>
        Password: <input type="password" name="password" required>
        Name: <input type="text" name="name" required>
        Phone: <input type="text" name="phone">
        <button type="submit">Add Member</button>
    </form>
    
    <h2>Trainers</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
        <input type="hidden" name="action" value="add_trainer">
        Username: <input type="text" name="username" required>
        Password: <input type="password" name="password" required>
        Name: <input type="text" name="name" required>
        Specialty: <input type="text" name="specialty">
        <button type="submit">Add Trainer</button>
    </form>
    
    <h2>Schedules</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
        <input type="hidden" name="action" value="add_schedule">
        Trainer: 
        <select name="trainer_id">
            <?php foreach ($trainers as $t): ?>
            <option value="<?php echo $t["id"]; ?>"><?php echo htmlspecialchars($t["name"]); ?></option>
            <?php endforeach; ?>
        </select>
        Class Name: <input type="text" name="class_name" required>
        Start Time: <input type="datetime-local" name="start_time" required>
        End Time: <input type="datetime-local" name="end_time" required>
        Capacity: <input type="number" name="capacity" required>
        <button type="submit">Add Schedule</button>
    </form>

    <h2>Renew Package</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
        <input type="hidden" name="action" value="renew_package">
        Member: 
        <select name="member_id">
            <?php foreach ($members as $m): ?>
            <option value="<?php echo htmlspecialchars($m["id"]); ?>"><?php echo htmlspecialchars($m["name"]); ?></option>
            <?php endforeach; ?>
        </select>
        Package: 
        <select name="package_id">
            <?php foreach ($packages as $p): ?>
            <option value="<?php echo htmlspecialchars($p["id"]); ?>"><?php echo htmlspecialchars($p["name"]); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Renew</button>
    </form>
</body>
</html>
