<?php
// includes/sidebar.php
if (!isset($conn)) {
    require __DIR__ . '/db.php';
}
?>
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="sidebar-header text-center">
        <img src="../assets/img/lcc-logo.png" alt="Liberty Christian Center Logo" class="sidebar-logo img-fluid">
        <h4 class="mt-2">LCC VBS Admin</h4>
    </div>
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>" 
                   href="dashboard.php" 
                   aria-current="<?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'page' : 'false' ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'register_child.php') ? 'active' : '' ?>" 
                   href="register_child.php"
                   aria-current="<?= (basename($_SERVER['PHP_SELF']) == 'register_child.php') ? 'page' : 'false' ?>">
                    <i class="bi bi-person-plus me-2"></i> Register Child
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'active' : '' ?>" 
                   href="attendance.php"
                   aria-current="<?= (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'page' : 'false' ?>">
                    <i class="bi bi-clipboard-check me-2"></i> Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : '' ?>" 
                   href="reports.php"
                   aria-current="<?= (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'page' : 'false' ?>">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'manage_admins.php') ? 'active' : '' ?>" 
                   href="manage_admins.php"
                   aria-current="<?= (basename($_SERVER['PHP_SELF']) == 'manage_admins.php') ? 'page' : 'false' ?>">
                    <i class="bi bi-people me-2"></i> Manage Admins
                </a>
            </li>
        </ul>
        
        <button class="btn btn-link d-md-none sidebar-toggle" type="button" data-bs-toggle="collapse" 
                data-bs-target="#sidebar" aria-expanded="true" aria-controls="sidebar">
            <i class="bi bi-chevron-double-left"></i> Collapse Menu
        </button>
    </div>
</nav>

<script>
    // Mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('.sidebar-toggle');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const isExpanded = sidebar.classList.contains('show');
                this.innerHTML = isExpanded 
                    ? '<i class="bi bi-chevron-double-right"></i> Expand Menu' 
                    : '<i class="bi bi-chevron-double-left"></i> Collapse Menu';
            });
        }
    });
</script>