<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require('../includes/db.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCC VBS - <?= htmlspecialchars(ucfirst(basename($_SERVER['PHP_SELF'], '.php'))) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/user.css">
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="user-theme">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <img src="../assets/img/lcc-logo.png" alt="LCC Logo" class="logo">
                <h4>VBS User Portal</h4>
            </div>
            <ul class="list-unstyled components">
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'register_child.php' ? 'active' : '' ?>">
                    <a href="register_child.php"><i class="bi bi-person-plus"></i> Register Child</a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>">
                    <a href="attendance.php"><i class="bi bi-clipboard-check"></i> Attendance</a>
                </li>
                <li>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="user-info ms-auto">
                        <i class="bi bi-person-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </div>
                </div>
            </nav>
            <div class="container-fluid">