<?php
require_once('../includes/auth.php');
require_once('../includes/header.php');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $class = $_POST['class'];
    $parent_phone = trim($_POST['parent_phone']);
    $payment_option = $_POST['payment_option'];
    $amount_paid = floatval($_POST['amount_paid']);

    // Validation
    if (empty($name) || empty($parent_phone)) {
        $error = "Name and Parent Phone are required!";
    } elseif (!preg_match('/^[0-9]{9}$/', $parent_phone)) {
        $error = "Please enter exactly 9 digits without 0 or +254 (e.g., 712345678)";
    } else {
        // Format phone number with +254 prefix for storage
        $full_phone = '+254' . $parent_phone;
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO children (name, class, parent_phone, payment_option, amount_paid) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $name, $class, $full_phone, $payment_option, $amount_paid);
        
        if ($stmt->execute()) {
            $success = "Child registered successfully!";
            $_POST = array(); // Clear form
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Register Child</h1>
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

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" id="registrationForm">
                        <div class="row g-3">
                            <!-- Child Name -->
                            <div class="col-md-6">
                                <label for="name" class="form-label required">Child's Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                       required
                                       minlength="3"
                                       pattern="[A-Za-z\s]+"
                                       title="Letters and spaces only">
                                <div class="invalid-feedback">Please enter a valid name (at least 3 letters)</div>
                            </div>

                            <!-- Class Selection -->
                            <div class="col-md-6">
                                <label for="class" class="form-label required">Class</label>
                                <select class="form-select" id="class" name="class" required>
                                    <option value="" disabled selected>Select Class</option>
                                    <option value="SHAMMAH" <?= ($_POST['class'] ?? '') == 'SHAMMAH' ? 'selected' : '' ?>>Grade 5 - 6 (SHAMMAH)</option>
                                    <option value="ROHI" <?= ($_POST['class'] ?? '') == 'ROHI' ? 'selected' : '' ?>>Grade 3 - 4 (ROHI)</option>
                                    <option value="NISSI" <?= ($_POST['class'] ?? '') == 'NISSI' ? 'selected' : '' ?>>Grade 1 - 2 (NISSI)</option>
                                    <option value="ELOHIM" <?= ($_POST['class'] ?? '') == 'ELOHIM' ? 'selected' : '' ?>>PP1 - PP2 (ELOHIM)</option>
                                    <option value="SHALOM" <?= ($_POST['class'] ?? '') == 'SHALOM' ? 'selected' : '' ?>>Age 3 (SHALOM)</option>
                                </select>
                                <div class="invalid-feedback">Please select a class</div>
                            </div>

                            <!-- Parent Phone -->
                            <div class="col-md-6">
                                <label for="parent_phone" class="form-label required">Parent Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">+254</span>
                                    <input type="tel" class="form-control" id="parent_phone" name="parent_phone" 
                                           value="<?= htmlspecialchars($_POST['parent_phone'] ?? '') ?>" 
                                           pattern="[0-9]{9}" 
                                           title="Enter 9 digits without 0 or +254 (e.g., 712345678)" 
                                           required
                                           maxlength="9">
                                </div>
                                <small class="form-text text-muted">Enter 9 digits (e.g., 712345678)</small>
                                <div class="invalid-feedback">Please enter 9 digits without 0 or +254</div>
                            </div>

                            <!-- Amount Paid -->
                            <div class="col-md-6">
                                <label for="amount_paid" class="form-label required">Amount Paid (KES)</label>
                                <input type="number" class="form-control" id="amount_paid" name="amount_paid" 
                                       value="<?= htmlspecialchars($_POST['amount_paid'] ?? '') ?>" 
                                       step="0.01" min="0" required>
                                <div class="invalid-feedback">Please enter a valid amount</div>
                            </div>

                            <!-- Payment Option -->
                            <div class="col-12">
                                <label class="form-label required">Payment Option</label>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="form-check card-option p-3 border rounded">
                                            <input class="form-check-input" type="radio" name="payment_option" id="cash" value="Cash" 
                                                   <?= ($_POST['payment_option'] ?? '') == 'Cash' ? 'checked' : '' ?> required>
                                            <label class="form-check-label card-option-label" for="cash">
                                                <i class="bi bi-cash-coin me-2"></i> Cash
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check card-option p-3 border rounded">
                                            <input class="form-check-input" type="radio" name="payment_option" id="mpesa" value="MPESA" 
                                                   <?= ($_POST['payment_option'] ?? '') == 'MPESA' ? 'checked' : '' ?> required>
                                            <label class="form-check-label card-option-label" for="mpesa">
                                                <i class="bi bi-phone me-2"></i> MPESA
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="invalid-feedback">Please select a payment option</div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end pt-3">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                                        <i class="bi bi-x-circle me-1"></i> Clear Form
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-person-plus me-1"></i> Register Child
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Enhanced client-side validation
document.getElementById('registrationForm').addEventListener('submit', function(event) {
    const form = event.target;
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
    
    // Phone number validation
    const phoneInput = document.getElementById('parent_phone');
    if (!/^[0-9]{9}$/.test(phoneInput.value)) {
        phoneInput.setCustomValidity("Please enter exactly 9 digits");
    } else {
        phoneInput.setCustomValidity("");
    }
});

// Prevent non-numeric input in phone field
document.getElementById('parent_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include('../includes/footer.php'); ?>