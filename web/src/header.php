<?php
// header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google" content="notranslate">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
    </style>
    <link rel="stylesheet" href="custom.css">
</head>
<body>
    <?php if (isset($_SESSION["user_id"])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-dumbbell text-warning"></i> Gym App</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isset($_SESSION["role"])): ?>
                        <?php if ($_SESSION["role"] === "manager"): ?>
                            <li class="nav-item"><a class="nav-link" href="manager.php">Manager</a></li>
                        <?php elseif ($_SESSION["role"] === "trainer"): ?>
                            <li class="nav-item"><a class="nav-link" href="trainer.php">Trainer</a></li>
                        <?php elseif ($_SESSION["role"] === "member"): ?>
                            <li class="nav-item"><a class="nav-link" href="member.php">Member</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <span class="navbar-text me-3">
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($_SESSION["role"] ?? ""); ?></span>
                </span>
                <a class="btn btn-outline-light btn-sm" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container pb-5">
