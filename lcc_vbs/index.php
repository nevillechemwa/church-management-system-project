<?php
session_start();

// Ensure proper path to db.php
require __DIR__ . '/includes/db.php';

// Prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input to prevent basic injection attempts
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password']; // No need to sanitize password before hashing/verifying

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Use prepared statements to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            // Verify password using password_verify (resistant to timing attacks)
            if (password_verify($password, $admin['password_hash'])) {
                // Regenerate session ID upon successful login to prevent session hijacking
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['username'] = htmlspecialchars($admin['username']); // Sanitize username for output
                $_SESSION['role'] = $admin['role'];
                $_SESSION['last_activity'] = time();
                
                // Redirect based on role
                if ($admin['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: user/dashboard.php');
                }
                exit;
            } else {
                // Don't give specific feedback on which part is wrong to prevent information leakage
                $error = 'Invalid username or password';
                // Optional: Implement basic rate limiting to prevent brute-force attacks
                sleep(1); // Add a small delay
            }
        } else {
            // Don't give specific feedback on which part is wrong
            $error = 'Invalid username or password';
            // Optional: Implement basic rate limiting
            sleep(1); // Add a small delay
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCC VBS - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <h2 class="mt-3">Liberty Christian Center</h2>
                <h4>VBS Portal</h4>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>