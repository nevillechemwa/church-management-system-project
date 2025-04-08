<?php
require_once('header.php');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $class = $_POST['class'];
    $parent_phone = trim($_POST['parent_phone']);
    $payment_option = $_POST['payment_option'];
    $amount_paid = floatval($_POST['amount_paid']);
    
    if (empty($name) || empty($parent_phone)) {
        $error = "Name and Parent Phone are required!";
    } elseif ($payment_option === 'MPESA' && $amount_paid <= 0) {
        $error = "Amount must be greater than 0 for MPESA payments";
    } else {
        $full_phone = '+254' . $parent_phone;
        $stmt = $conn->prepare("INSERT INTO children (name, class, parent_phone, payment_option, amount_paid) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $name, $class, $full_phone, $payment_option, $amount_paid);
        
        if ($stmt->execute()) {
            $success = "Child registered successfully!";
            $_POST = array();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Register New Child</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Child's Full Name</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Class</label>
                <select class="form-select" name="class" required>
                    <option value="SHAMMAH" <?= ($_POST['class'] ?? '') === 'SHAMMAH' ? 'selected' : '' ?>>Grade 5-6 (SHAMMAH)</option>
                    <option value="ROHI" <?= ($_POST['class'] ?? '') === 'ROHI' ? 'selected' : '' ?>>Grade 3-4 (ROHI)</option>
                    <option value="NISSI" <?= ($_POST['class'] ?? '') === 'NISSI' ? 'selected' : '' ?>>Grade 1-2 (NISSI)</option>
                    <option value="ELOHIM" <?= ($_POST['class'] ?? '') === 'ELOHIM' ? 'selected' : '' ?>>PP1-PP2 (ELOHIM)</option>
                    <option value="SHALOM" <?= ($_POST['class'] ?? '') === 'SHALOM' ? 'selected' : '' ?>>Age 3 (SHALOM)</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Parent Phone Number</label>
                <div class="input-group">
                    <span class="input-group-text">+254</span>
                    <input type="tel" class="form-control" name="parent_phone" maxlength="9" value="<?= htmlspecialchars($_POST['parent_phone'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select class="form-select" name="payment_option" id="payment_option" required>
                    <option value="Cash" <?= ($_POST['payment_option'] ?? '') === 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="MPESA" <?= ($_POST['payment_option'] ?? '') === 'MPESA' ? 'selected' : '' ?>>MPESA</option>
                </select>
            </div>
            
            <div class="mb-3" id="amount_field">
                <label class="form-label">Amount Paid (KES)</label>
                <input type="number" class="form-control" name="amount_paid" step="0.01" min="0" value="<?= htmlspecialchars($_POST['amount_paid'] ?? '0.00') ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Register Child
            </button>
        </form>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(document).ready(function() {
    // Sidebar toggle
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });
    
    // Auto-close alerts
    setTimeout(function() {
        $('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove(); 
        });
    }, 5000);
    
    // Handle payment option changes
    $('#payment_option').change(function() {
        if ($(this).val() === 'Cash') {
            $('input[name="amount_paid"]').val('0.00');
        }
    });
    
    // Validate amount for MPESA
    $('form').submit(function() {
        if ($('#payment_option').val() === 'MPESA' && parseFloat($('input[name="amount_paid"]').val()) <= 0) {
            alert('Please enter a valid amount for MPESA payment');
            return false;
        }
        return true;
    });
});
</script>