<?php
require_once('header.php');

$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if (!in_array($action, ['check_in', 'check_out'])) {
    header('Location: attendance.php');
    exit;
}

$child = $conn->query("SELECT id, name, parent_phone FROM children WHERE id = $child_id")->fetch_assoc();
if (!$child) {
    header('Location: attendance.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $time = date('H:i:s');
    $by_parent = ($_POST['person_type'] == 'parent') ? 1 : 0;
    
    if (!$by_parent && (empty($_POST['person_name']) || empty($_POST['person_phone']))) {
        $error = "Please provide both name and phone number when someone else is dropping/picking";
    } else {
        $person_name = $by_parent ? 'Parent' : trim($_POST['person_name']);
        $person_phone = $by_parent ? $child['parent_phone'] : '+254'.trim($_POST['person_phone']);

        try {
            if ($action == 'check_in') {
                // Check if already checked in today
                $existing = $conn->query("SELECT id FROM attendance WHERE child_id = $child_id AND date = '$date'")->num_rows;
                
                if ($existing) {
                    $stmt = $conn->prepare("
                        UPDATE attendance SET
                        check_in = ?,
                        dropped_by = ?,
                        dropped_by_phone = ?,
                        dropped_by_parent = ?
                        WHERE child_id = ?
                        AND date = ?
                    ");
                    $stmt->bind_param("ssssis", $time, $person_name, $person_phone, $by_parent, $child_id, $date);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO attendance 
                        (child_id, date, check_in, dropped_by, dropped_by_phone, dropped_by_parent)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("issssi", $child_id, $date, $time, $person_name, $person_phone, $by_parent);
                }
            } else {
                // Check if already checked out today
                $checked_out = $conn->query("SELECT id FROM attendance WHERE child_id = $child_id AND date = '$date' AND check_out IS NOT NULL")->num_rows;
                
                if ($checked_out) {
                    $_SESSION['error'] = "This child has already been checked out today";
                    header("Location: attendance.php?date=$date");
                    exit;
                }
                
                $stmt = $conn->prepare("
                    UPDATE attendance SET
                    check_out = ?,
                    picked_by = ?,
                    picked_by_phone = ?,
                    picked_by_parent = ?
                    WHERE child_id = ?
                    AND date = ?
                ");
                $stmt->bind_param("ssssis", $time, $person_name, $person_phone, $by_parent, $child_id, $date);
            }

            if ($stmt->execute()) {
                $_SESSION['success'] = "Child successfully ".($action == 'check_in' ? 'checked in' : 'checked out');
                header("Location: attendance.php?date=$date");
                exit;
            } else {
                $error = "Database error: ".$conn->error;
            }
        } catch (Exception $e) {
            $error = "Error: ".$e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><?= ucfirst(str_replace('_', ' ', $action)) ?> - <?= htmlspecialchars($child['name']) ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="attendanceForm">
            <div class="mb-3">
                <label class="form-label">Child Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($child['name']) ?>" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="text" class="form-control" value="<?= date('F j, Y', strtotime($date)) ?>" readonly>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Who is <?= $action == 'check_in' ? 'dropping' : 'picking' ?> the child?</label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="person_type" id="parentType" value="parent" checked>
                    <label class="form-check-label" for="parentType">
                        Parent (Phone: <?= htmlspecialchars($child['parent_phone']) ?>)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="person_type" id="otherType" value="other">
                    <label class="form-check-label" for="otherType">
                        Someone else
                    </label>
                </div>
            </div>
            
            <div id="otherPersonFields" style="display: none;">
                <div class="mb-3">
                    <label for="person_name" class="form-label">Person's Name</label>
                    <input type="text" class="form-control" name="person_name" id="person_name">
                </div>
                <div class="mb-3">
                    <label for="person_phone" class="form-label">Phone Number</label>
                    <div class="input-group">
                        <span class="input-group-text">+254</span>
                        <input type="tel" class="form-control" name="person_phone" id="person_phone" maxlength="9">
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-check-circle"></i> Confirm <?= ucfirst(str_replace('_', ' ', $action)) ?>
                </button>
                <a href="attendance.php?date=<?= $date ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle other person fields
    document.querySelectorAll('input[name="person_type"]').forEach(el => {
        el.addEventListener('change', function() {
            const fields = document.getElementById('otherPersonFields');
            fields.style.display = this.value === 'other' ? 'block' : 'none';
            
            // Toggle required attribute
            document.getElementById('person_name').required = this.value === 'other';
            document.getElementById('person_phone').required = this.value === 'other';
        });
    });

    // Prevent non-numeric input in phone fields
    document.getElementById('person_phone')?.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Form submission handler
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    });
});
</script>

<?php include('../includes/footer.php'); ?>