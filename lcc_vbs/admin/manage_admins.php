<?php 
ob_start(); 
require_once('../includes/auth.php'); 
include('../includes/header.php');

// Update current admin's last activity (every 30 seconds)
if (!isset($_SESSION['last_activity_update']) || time() - $_SESSION['last_activity_update'] > 30) {
    $conn->query("UPDATE admins SET last_activity = NOW(), is_active = 1 WHERE id = ".$_SESSION['admin_id']);
    $_SESSION['last_activity_update'] = time();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'user';
        
        $stmt = $conn->prepare("INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        
        if ($stmt->execute()) {
            logAdminActivity($_SESSION['admin_id'], 'admin_create', "Created new admin: $username");
            $_SESSION['success'] = "New admin registered successfully!";
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
    } 
    elseif (isset($_POST['update_role'])) {
        $admin_id = intval($_POST['admin_id']);
        $role = $_POST['role'];
        
        $stmt = $conn->prepare("UPDATE admins SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $admin_id);
        
        if ($stmt->execute()) {
            $username = $conn->query("SELECT username FROM admins WHERE id = $admin_id")->fetch_row()[0];
            logAdminActivity($_SESSION['admin_id'], 'role_update', "Updated role for $username to $role");
            $_SESSION['success'] = "Role updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating role: " . $conn->error;
        }
    }
    elseif (isset($_POST['reset_password'])) {
        $admin_id = intval($_POST['admin_id']);
        $new_password = trim($_POST['new_password']);
        
        if (empty($new_password)) {
            $_SESSION['error'] = "Please enter a new password";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $password_hash, $admin_id);
            
            if ($stmt->execute()) {
                $username = $conn->query("SELECT username FROM admins WHERE id = $admin_id")->fetch_row()[0];
                logAdminActivity($_SESSION['admin_id'], 'password_reset', "Reset password for $username");
                $_SESSION['success'] = "Password reset successfully!";
            } else {
                $_SESSION['error'] = "Error resetting password: " . $conn->error;
            }
        }
    }
    
    header("Location: manage_admins.php");
    ob_end_flush();
    exit();
}

// Handle admin deletion
if (isset($_GET['delete'])) {
    $admin_id = intval($_GET['delete']);
    
    if ($admin_id != $_SESSION['admin_id']) {
        $username = $conn->query("SELECT username FROM admins WHERE id = $admin_id")->fetch_row()[0];
        
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        
        if ($stmt->execute()) {
            logAdminActivity($_SESSION['admin_id'], 'admin_delete', "Deleted admin: $username");
            $_SESSION['success'] = "Admin deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting admin: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "You cannot delete yourself!";
    }
    
    header("Location: manage_admins.php");
    ob_end_flush();
    exit();
}

// Fetch all admins with activity status (updated to show active users correctly)
$admins = $conn->query("
    SELECT id, username, role, created_at, last_activity, last_login, 
           (last_activity > NOW() - INTERVAL 5 MINUTE) as is_active 
    FROM admins 
    ORDER BY is_active DESC, last_activity DESC
");

// Fetch recent activities
$activities = $conn->query("
    SELECT a.username, l.activity_type, l.description, l.created_at 
    FROM admin_activity_log l
    JOIN admins a ON l.admin_id = a.id
    ORDER BY l.created_at DESC 
    LIMIT 10
");

// Function to log admin activities
function logAdminActivity($admin_id, $activity_type, $description) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("
        INSERT INTO admin_activity_log 
        (admin_id, activity_type, description, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $admin_id, $activity_type, $description, $ip);
    $stmt->execute();
}

// Function to generate random password
function generateRandomPassword($length = 12) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>

<main class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Admin Management Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#activityLogModal">
                    <i class="bi bi-clock-history"></i> View Full Activity Log
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Admin Registration Card -->
        <div class="col-lg-5">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Register New Admin</h5>
                        <i class="bi bi-person-plus fs-4"></i>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" id="generatePassword">
                                    <i class="bi bi-shuffle"></i> Generate
                                </button>
                            </div>
                            <div class="form-text">Minimum 8 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">User (Can register children and mark attendance)</option>
                                <option value="admin">Admin (Full access)</option>
                            </select>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus"></i> Register Admin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Activities Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Activities</h5>
                        <i class="bi bi-activity fs-4"></i>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        <?php if ($activities->num_rows > 0): ?>
                            <?php while ($activity = $activities->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($activity['username']) ?></h6>
                                        <small><?= time_elapsed_string($activity['created_at']) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                    <small class="text-muted"><?= ucfirst($activity['activity_type']) ?></small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center text-muted py-3">
                                No recent activities found
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin List Card -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Admin Users</h5>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-people-fill"></i> <?= $admins->num_rows ?> admins
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($admin = $admins->fetch_assoc()): 
                                    $is_current = $admin['id'] == $_SESSION['admin_id'];
                                    $is_active = $admin['is_active'];
                                    $last_activity = $admin['last_activity'] ? new DateTime($admin['last_activity']) : null;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?= htmlspecialchars($admin['username']) ?>
                                            <?php if ($is_current): ?>
                                                <span class="badge bg-success ms-2">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <select class="form-select form-select-sm" name="role" onchange="this.form.submit()" <?= $is_current ? 'disabled' : '' ?>>
                                                <option value="user" <?= $admin['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                                <option value="admin" <?= $admin['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge <?= $is_active ? 'bg-success' : 'bg-secondary' ?>">
                                            <i class="bi bi-circle-fill"></i> 
                                            <?= $is_active ? 'Active' : 'Inactive' ?>
                                            <?php if ($is_active && $last_activity): ?>
                                                <span class="visually-hidden">for <?= time_elapsed_string($admin['last_activity']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($last_activity): ?>
                                            <small data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $last_activity->format('M j, Y g:i A') ?>">
                                                <?= time_elapsed_string($admin['last_activity']) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Never</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (!$is_current): ?>
                                                <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['username']) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#passwordModal" 
                                                    onclick="setAdminId(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['username']) ?>')">
                                                    <i class="bi bi-key"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary" disabled>
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small>Last updated: <?= date('M j, Y g:i A') ?></small>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Activity Log Modal -->
<div class="modal fade" id="activityLogModal" tabindex="-1" aria-labelledby="activityLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="activityLogModalLabel">Full Activity Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Activity</th>
                                <th>Type</th>
                                <th>Time</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $full_activities = $conn->query("
                                SELECT a.username, l.activity_type, l.description, l.created_at, l.ip_address 
                                FROM admin_activity_log l
                                JOIN admins a ON l.admin_id = a.id
                                ORDER BY l.created_at DESC 
                                LIMIT 50
                            ");
                            if ($full_activities->num_rows > 0): 
                                while ($activity = $full_activities->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($activity['username']) ?></td>
                                    <td><?= htmlspecialchars($activity['description']) ?></td>
                                    <td><span class="badge bg-secondary"><?= ucfirst($activity['activity_type']) ?></span></td>
                                    <td><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></td>
                                    <td><small><?= $activity['ip_address'] ?></small></td>
                                </tr>
                                <?php endwhile; 
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No activity records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="passwordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="admin_id" id="modalAdminId">
                    <div class="mb-3">
                        <label for="adminUsername" class="form-label">Admin Username</label>
                        <input type="text" class="form-control" id="adminUsername" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="new_password" name="new_password" required>
                            <button type="button" class="btn btn-outline-secondary" id="generateNewPassword">
                                <i class="bi bi-shuffle"></i> Generate
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Generate random password
document.getElementById('generatePassword').addEventListener('click', function() {
    const password = generatePassword();
    document.getElementById('password').value = password;
    document.getElementById('password').type = 'text';
});

// Generate new password in modal
document.getElementById('generateNewPassword').addEventListener('click', function() {
    const password = generatePassword();
    document.getElementById('new_password').value = password;
});

function generatePassword() {
    const chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars[Math.floor(Math.random() * chars.length)];
    }
    return password;
}

// Set admin ID and username in password modal
function setAdminId(id, username) {
    document.getElementById('modalAdminId').value = id;
    document.getElementById('adminUsername').value = username;
    document.getElementById('new_password').value = '';
}

// Confirm admin deletion
function confirmDelete(id, username) {
    if (confirm(`Are you sure you want to permanently delete admin "${username}"?`)) {
        window.location.href = `manage_admins.php?delete=${id}`;
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php 
// Helper function to display time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

include('../includes/footer.php'); 
ob_end_flush();
?>