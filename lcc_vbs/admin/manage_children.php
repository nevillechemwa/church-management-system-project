<?php
// Ensure no output before session_start()
ob_start();

require_once('../includes/auth.php');
require_once('../includes/header.php');

$error = "";
$success = "";

// Search functionality with AJAX support
$search = $_GET['search'] ?? '';
$where = "WHERE is_active = TRUE";
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR parent_phone LIKE '%$search%' OR class LIKE '%$search%')";
}

// Handle deletion with password confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $child_id = intval($_POST['child_id']);
    $password = $_POST['password'];

    // Verify admin password
    $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if (password_verify($password, $admin['password_hash'])) {
        // Soft delete (set is_active = FALSE)
        $stmt = $conn->prepare("UPDATE children SET is_active = FALSE WHERE id = ?");
        $stmt->bind_param("i", $child_id);
        if ($stmt->execute()) {
            $success = "Child record deleted successfully!";
            header("Location: manage_children.php");
            ob_end_flush(); // Send buffered output and headers
            exit();
        } else {
            $error = "Error deleting record: " . $conn->error;
        }
    } else {
        $error = "Incorrect password!";
    }
}

// Handle edits via modal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $child_id = intval($_POST['child_id']);
    $name = trim($_POST['name']);
    $class = $_POST['class'];
    $parent_phone = trim($_POST['parent_phone']);

    $stmt = $conn->prepare("UPDATE children SET name=?, class=?, parent_phone=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $class, $parent_phone, $child_id);

    if ($stmt->execute()) {
        $success = "Record updated successfully!";
        header("Location: manage_children.php");
        ob_end_flush(); // Send buffered output and headers
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}

// Get children data
$children = $conn->query("SELECT * FROM children $where ORDER BY name");
?>

<main class="col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Children</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="register_child.php" class="btn btn-sm btn-primary me-2">
                <i class="bi bi-person-plus"></i> Register New
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Children Records</h6>
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <input type="text" class="form-control" id="liveSearch" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="childrenTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Parent Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($child = $children->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($child['name']) ?></td>
                                <td><?= htmlspecialchars($child['class']) ?></td>
                                <td><?= htmlspecialchars($child['parent_phone']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="<?= $child['id'] ?>"
                                            data-name="<?= htmlspecialchars($child['name']) ?>"
                                            data-class="<?= htmlspecialchars($child['class']) ?>"
                                            data-phone="<?= htmlspecialchars($child['parent_phone']) ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-id="<?= $child['id'] ?>"
                                            data-name="<?= htmlspecialchars($child['name']) ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Child Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="child_id" id="editChildId">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editClass" class="form-label">Class</label>
                        <select class="form-select" id="editClass" name="class" required>
                            <?php
                            $classes = ['SHAMMAH', 'ROHI', 'NISSI', 'ELOHIM', 'SHALOM'];
                            foreach ($classes as $class): ?>
                                <option value="<?= $class ?>"><?= $class ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Parent Phone</label>
                        <input type="tel" class="form-control" id="editPhone" name="parent_phone" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>You are about to delete <strong id="deleteChildName"></strong>. This action cannot be undone.</p>
                    <input type="hidden" name="child_id" id="deleteChildId">
                    <div class="mb-3">
                        <label for="password" class="form-label">Enter Your Password to Confirm</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit modal population
document.getElementById('editModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('editChildId').value = button.getAttribute('data-id');
    document.getElementById('editName').value = button.getAttribute('data-name');
    document.getElementById('editClass').value = button.getAttribute('data-class');
    document.getElementById('editPhone').value = button.getAttribute('data-phone');
});

// Delete modal population
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('deleteChildId').value = button.getAttribute('data-id');
    document.getElementById('deleteChildName').textContent = button.getAttribute('data-name');
});

// Live search functionality
document.getElementById('liveSearch').addEventListener('input', function() {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#childrenTable tbody tr');

    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const className = row.cells[1].textContent.toLowerCase();
        const phone = row.cells[2].textContent.toLowerCase();

        if (name.includes(searchValue) || className.includes(searchValue) || phone.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include('../includes/footer.php'); ?>
<?php
// Ensure no extra output after the main content and footer
ob_end_flush();
?>