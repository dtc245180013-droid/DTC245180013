<?php
require_once "config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION["role"];
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (<?php echo htmlspecialchars($role); ?>)</h1>
    <a href="logout.php">Logout</a>
    <hr>
    <?php
    if ($role === "manager") {
        echo "<a href='manager.php'>Manager Dashboard</a>";
    } elseif ($role === "trainer") {
        echo "<a href='trainer.php'>Trainer Dashboard</a>";
    } elseif ($role === "member") {
        echo "<a href='member.php'>Member Dashboard</a>";
    }
    ?>
</body>
</html>
