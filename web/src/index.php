<?php
require_once "config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION["role"];
?>
<?php require_once "header.php"; ?>
<div class="row mt-4">
    <div class="col-md-12 text-center">
        <h1 class="display-4"><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h1>
        <p class="lead mt-3">You are logged in as a <strong><span class="badge bg-secondary"><?php echo htmlspecialchars($role); ?></span></strong>.</p>
        <hr class="my-4">
        
        <?php if ($role === "manager"): ?>
            <a href='manager.php' class='btn btn-primary btn-lg m-2'><i class="fas fa-chart-line"></i> Go to Manager Dashboard</a>
        <?php elseif ($role === "trainer"): ?>
            <a href='trainer.php' class='btn btn-success btn-lg m-2'><i class="fas fa-stopwatch"></i> Go to Trainer Dashboard</a>
        <?php elseif ($role === "member"): ?>
            <a href='member.php' class='btn btn-info btn-lg m-2 text-white'><i class="fas fa-calendar-alt"></i> Go to Member Dashboard</a>
        <?php endif; ?>
    </div>
</div>
<?php require_once "footer.php"; ?>
