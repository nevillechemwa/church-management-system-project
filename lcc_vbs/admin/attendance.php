<?php
// admin_attendance.php - Adjusted to handle existing data, new fields, and records per page
ob_start();
require_once('../includes/auth.php');
require_once('../includes/header.php');

date_default_timezone_set('Africa/Nairobi');
$date = $_GET['date'] ?? date('Y-m-d');
$error = "";
$success = "";
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all'; // Added status filter
$page = $_GET['page'] ?? 1; // Add page number
$records_per_page_options = [25, 50, 75, 100, 'all'];
$records_per_page = $_GET['records'] ?? 10; // Default to 10 if not set

// Adjust records per page if 'all' is selected
if ($records_per_page === 'all') {
    $limit_clause = "";
    $records_per_page_display = 'All';
} else {
    $records_per_page = intval($records_per_page);
    $limit_clause = "LIMIT $records_per_page OFFSET " . (($page - 1) * $records_per_page);
    $records_per_page_display = $records_per_page;
}

// Function to safely get a value from an array
function get_array_value(array $arr, string $key, $default = null)
{
    return array_key_exists($key, $arr) ? $arr[$key] : $default;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['child_id'])) {
        $child_id = intval($_POST['child_id']);
        $action = $_POST['action'];
        $time = date('H:i:s');

        // Handle drop-off/pick-up details
        $dropped_by = isset($_POST['dropped_by']) ? trim($_POST['dropped_by']) : null;
        $dropped_by_phone = isset($_POST['dropped_by_phone']) ? trim($_POST['dropped_by_phone']) : null;
        $picked_by = isset($_POST['picked_by']) ? trim($_POST['picked_by']) : null;
        $picked_by_phone = isset($_POST['picked_by_phone']) ? trim($_POST['picked_by_phone']) : null;
        $dropped_by_parent = isset($_POST['dropper_type']) ? ($_POST['dropper_type'] === 'parent' ? 1 : 0) : 0;
        $picked_by_parent = isset($_POST['picker_type']) ? ($_POST['picker_type'] === 'parent' ? 1 : 0) : 0;
        $parent_phone = $_POST['parent_phone'] ?? null; // Get parent phone from form

        try {
            if ($action == 'check_in') {
                $stmt = $conn->prepare("
                    INSERT INTO attendance (child_id, date, check_in, dropped_by, dropped_by_phone, dropped_by_parent)
                    SELECT ?, ?, ?, ?, ?, ? FROM DUAL
                    WHERE NOT EXISTS (
                        SELECT 1 FROM attendance
                        WHERE child_id = ? AND date = ?
                    )
                    ON DUPLICATE KEY UPDATE
                        check_in = VALUES(check_in),
                        dropped_by = VALUES(dropped_by),  -- Corrected line
                        dropped_by_phone = VALUES(dropped_by_phone),
                        dropped_by_parent = VALUES(dropped_by_parent);
                ");

                // Use the parent's phone number if the dropper is the parent
                $effective_dropped_by_phone = $dropped_by_parent ? $parent_phone : $dropped_by_phone;
                // Set dropped_by value
                $dropped_by_value = $dropped_by_parent ? 'Parent' : $dropped_by; //added line
                $stmt->bind_param("issssiis", $child_id, $date, $time, $dropped_by_value, $effective_dropped_by_phone, $dropped_by_parent, $child_id, $date); //modified line

                if ($stmt->execute()) {
                    $success = "Child checked in successfully!";
                } else {
                    $error = "Error checking in child: " . $stmt->error;
                }
                $stmt->close();
            } else { // check_out
                $stmt = $conn->prepare("
                    UPDATE attendance
                    SET check_out = ?, picked_by = ?, picked_by_phone = ?, picked_by_parent = ?
                    WHERE child_id = ? AND date = ? AND check_out IS NULL
                ");
                // Use the parent's phone number if the picker is the parent
                $effective_picked_by_phone = $picked_by_parent ? $parent_phone : $picked_by_phone;
                // Set picked_by value
                $picked_by_value = $picked_by_parent ? 'Parent' : $picked_by;
                $stmt->bind_param("ssssis", $time, $picked_by_value, $effective_picked_by_phone, $picked_by_parent, $child_id, $date);
                if ($stmt->execute()) {
                    $success = "Child checked out successfully!";
                } else {
                    $error = "Error checking out child: " . $stmt->error;
                }
                $stmt->close();
            }
            // header("Location: attendance.php?date=$date"); // Removed to prevent conflicts with pagination
            // ob_end_flush();  // Removed to prevent "headers already sent" error.  The exit() was causing a loop.
            // exit();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Build search query
$search_condition = "";
if (!empty($search_term)) {
    $search_term = $conn->real_escape_string($search_term);
    $search_condition = "AND c.name LIKE '%$search_term%'";
}

// Build status filter
$status_condition = "";
if ($status_filter == 'checked_in') {
    $status_condition = "AND a.check_in IS NOT NULL AND a.check_out IS NULL";
} elseif ($status_filter == 'checked_out') {
    $status_condition = "AND a.check_in IS NOT NULL AND a.check_out IS NOT NULL";
} elseif ($status_filter == 'not_checked_in') {
    $status_condition = "AND a.check_in IS NULL";
}

// Fetch attendance data with pagination
$attendance_query = "
    SELECT c.id, c.name, c.class, c.parent_phone,
            a.check_in, a.check_out,
            a.dropped_by, a.dropped_by_phone, a.dropped_by_parent,
            a.picked_by, a.picked_by_phone, a.picked_by_parent
    FROM children c
    LEFT JOIN attendance a ON c.id = a.child_id AND a.date = '$date'
    WHERE c.is_active = TRUE $search_condition $status_condition
    ORDER BY c.class, c.name
    $limit_clause
";

$attendance = $conn->query($attendance_query);

// Get total number of records for pagination
$total_records_query = "
    SELECT COUNT(*) FROM children c
    LEFT JOIN attendance a ON c.id = a.child_id AND a.date = '$date'
    WHERE c.is_active = TRUE $search_condition $status_condition
";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_row()[0];
$total_pages = ($records_per_page === 'all' || $records_per_page <= 0) ? 1 : ceil($total_records / $records_per_page);
?>

<main class="col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Daily Attendance</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="reports.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-file-earmark-bar-graph"></i> View Reports
                </a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= htmlspecialchars(date('l, F j, Y', strtotime($date))) ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search_term) ?>" class="form-control"
                           placeholder="Search child by name...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All</option>
                        <option value="checked_in" <?= $status_filter == 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                        <option value="checked_out" <?= $status_filter == 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                        <option value="not_checked_in" <?= $status_filter == 'not_checked_in' ? 'selected' : '' ?>>Not Checked In</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="records" class="form-label">Records per page</label>
                    <select name="records" id="records" class="form-select">
                        <?php foreach ($records_per_page_options as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>" <?= $records_per_page == $option ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($option)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                </div>
            </form>

            <?php if (!empty($search_term) && $attendance->num_rows === 0): ?>
                <div class="alert alert-warning text-center">
                    No child found with name containing "<?= htmlspecialchars($search_term) ?>"
                    <div class="mt-3">
                        <a href="register_child.php" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Register New Child
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="attendanceTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $row_number = ($page - 1) * ($records_per_page === 'all' ? $total_records : $records_per_page) + 1; ?>
                            <?php while ($row = $attendance->fetch_assoc()): ?>
                                <tr class="<?= $row['check_in'] ? ($row['check_out'] ? 'table-success' : 'table-warning') : 'table-danger' ?>">
                                    <td><?= $row_number++ ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['class']) ?></td>
                                    <td>
                                        <?php if ($row['check_in']): ?>
                                            <?= date('h:i A', strtotime($row['check_in'])) ?>
                                            <br>
                                            <small class="text-muted">
                                                Dropped by:
                                                <?= $row['dropped_by_parent'] ? 'Parent' : (htmlspecialchars($row['dropped_by'] ?? '')) ?>
                                                <?php if ($row['dropped_by_phone'] || $row['dropped_by_parent']): ?>
                                                    (<?= $row['dropped_by_parent'] ? htmlspecialchars($row['parent_phone']) : htmlspecialchars($row['dropped_by_phone']) ?>)
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['check_out']): ?>
                                            <?= date('h:i A', strtotime($row['check_out'])) ?>
                                            <br>
                                            <small class="text-muted">
                                                Picked by:
                                                <?= $row['picked_by_parent'] ? 'Parent' : (htmlspecialchars($row['picked_by'] ?? '')) ?>
                                                <?php if ($row['picked_by_phone'] || $row['picked_by_parent']): ?>
                                                    (<?= $row['picked_by_parent'] ? htmlspecialchars($row['parent_phone']) : htmlspecialchars($row['picked_by_phone']) ?>)
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['check_in'] && $row['check_out']): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($row['check_in']): ?>
                                            <span class="badge bg-warning text-dark">Checked In</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Absent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$row['check_in']): ?>
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                    data-bs-target="#checkInModal"
                                                    data-child-id="<?= $row['id'] ?>"
                                                    data-child-name="<?= htmlspecialchars($row['name']) ?>"
                                                    data-parent-phone="<?= htmlspecialchars($row['parent_phone']) ?>">
                                                <i class="bi bi-box-arrow-in-right"></i> Check In
                                            </button>
                                        <?php elseif (!$row['check_out']): ?>
                                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                    data-bs-target="#checkOutModal"
                                                    data-child-id="<?= $row['id'] ?>"
                                                    data-child-name="<?= htmlspecialchars($row['name']) ?>"
                                                     data-parent-phone="<?= htmlspecialchars($row['parent_phone']) ?>">
                                                <i class="bi bi-box-arrow-right"></i> Check Out
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_pages > 1 && $records_per_page !== 'all'): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?date=<?= htmlspecialchars($date) ?>&search=<?= htmlspecialchars($search_term) ?>&status=<?= htmlspecialchars($status_filter) ?>&records=<?= htmlspecialchars($records_per_page) ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?date=<?= htmlspecialchars($date) ?>&search=<?= htmlspecialchars($search_term) ?>&status=<?= htmlspecialchars($status_filter) ?>&records=<?= htmlspecialchars($records_per_page) ?>&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?date=<?= htmlspecialchars($date) ?>&search=<?= htmlspecialchars($search_term) ?>&status=<?= htmlspecialchars($status_filter) ?>&records=<?= htmlspecialchars($records_per_page) ?>&page=<?= $page + 1 ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<div class="modal fade" id="checkInModal" tabindex="-1" aria-labelledby="checkInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="child_id" id="checkInChildId">
                <input type="hidden" name="action" value="check_in">
                <input type="hidden" name="parent_phone" id="checkInParentPhone">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkInModalLabel">Check In Child</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Child Name</label>
                        <input type="text" class="form-control" id="checkInChildName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Who is dropping the child?</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="dropper_type" id="parentDrop"
                                   value="parent" checked>
                            <label class="form-check-label" for="parentDrop">
                                Parent (Phone: <span id="parentPhoneDisplay"></span>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="dropper_type" id="otherDrop" value="other">
                            <label class="form-check-label" for="otherDrop">
                                Someone else
                            </label>
                        </div>
                    </div>
                    <div id="otherDropperFields" style="display: none;">
                        <div class="mb-3">
                            <label for="dropped_by" class="form-label">Person's Name</label>
                            <input type="text" class="form-control" name="dropped_by" id="dropped_by">
                        </div>
                        <div class="mb-3">
                            <label for="dropped_by_phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">+254</span>
                                <input type="tel" class="form-control" name="dropped_by_phone" id="dropped_by_phone"
                                       maxlength="9">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Check In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="checkOutModal" tabindex="-1" aria-labelledby="checkOutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="child_id" id="checkOutChildId">
                <input type="hidden" name="action" value="check_out">
                <input type="hidden" name="parent_phone" id="checkOutParentPhone">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkOutModalLabel">Check Out Child</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Child Name</label>
                        <input type="text" class="form-control" id="checkOutChildName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Who is picking the child?</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="picker_type" id="parentPick"
                                   value="parent" checked>
                            <label class="form-check-label" for="parentPick">
                                Parent (Phone: <span id="parentPhoneDisplay2"></span>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="picker_type" id="otherPick" value="other">
                            <label class="form-check-label" for="otherPick">
                                Someone else
                            </label>
                        </div>
                    </div>
                    <div id="otherPickerFields" style="display: none;">
                        <div class="mb-3">
                            <label for="picked_by" class="form-label">Person's Name</label>
                            <input type="text" class="form-control" name="picked_by" id="picked_by">
                        </div>
                        <div class="mb-3">
                            <label for="picked_by_phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">+254</span>
                                <input type="tel" class="form-control" name="picked_by_phone" id="picked_by_phone"
                                       maxlength="9">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Check Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Check In Modal
document.getElementById('checkInModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const childId = button.getAttribute('data-child-id');
    const childName = button.getAttribute('data-child-name');
    const parentPhone = button.getAttribute('data-parent-phone');

    document.getElementById('checkInChildId').value = childId;
    document.getElementById('checkInChildName').value = childName;
    document.getElementById('parentPhoneDisplay').textContent = '+254' + parentPhone;
    document.getElementById('checkInParentPhone').value = parentPhone; //set the value in the hidden field
});

// Check Out Modal
document.getElementById('checkOutModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const childId = button.getAttribute('data-child-id');
    const childName = button.getAttribute('data-child-name');
    const parentPhone = button.getAttribute('data-parent-phone');

    document.getElementById('checkOutChildId').value = childId;
    document.getElementById('checkOutChildName').value = childName;
document.getElementById('parentPhoneDisplay2').textContent = '+254' + parentPhone;
    document.getElementById('checkOutParentPhone').value = parentPhone; //set the value in the hidden field
});

// Toggle other dropper fields
document.querySelectorAll('input[name="dropper_type"]').forEach(el => {
    el.addEventListener('change', function () {
        document.getElementById('otherDropperFields').style.display =
            this.value === 'other' ? 'block' : 'none';
    });
});

// Toggle other picker fields
document.querySelectorAll('input[name="picker_type"]').forEach(el => {
    el.addEventListener('change', function () {
        document.getElementById('otherPickerFields').style.display =
            this.value === 'other' ? 'block' : 'none';
    });
});

// Prevent non-numeric input in phone fields
document.querySelectorAll('input[type="tel"]').forEach(el => {
    el.addEventListener('input', function (e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>

<?php include('../includes/footer.php'); ?>
<?php ob_end_flush(); ?>
