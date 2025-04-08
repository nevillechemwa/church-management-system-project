<?php
require_once('../includes/auth.php');
require_once('../includes/header.php');

$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Default to today

// Get total number of active children
$total_children = $conn->query("SELECT COUNT(*) FROM children WHERE is_active = TRUE")->fetch_row()[0];

// Get payment summary
$payment_summary = $conn->query("
    SELECT 
        SUM(amount_paid) as total_amount,
        SUM(CASE WHEN payment_option = 'Cash' THEN amount_paid ELSE 0 END) as cash_total,
        SUM(CASE WHEN payment_option = 'MPESA' THEN amount_paid ELSE 0 END) as mpesa_total,
        COUNT(CASE WHEN amount_paid > 0 THEN 1 END) as paid_count,
        COUNT(CASE WHEN amount_paid = 0 THEN 1 END) as not_paid_count
    FROM children 
    WHERE is_active = TRUE
")->fetch_assoc();

// Get today's attendance stats
$today = date('Y-m-d');
$today_stats = $conn->query("
    SELECT 
        COUNT(*) as checked_in,
        COUNT(CASE WHEN check_out IS NOT NULL THEN 1 END) as checked_out,
        COUNT(CASE WHEN picked_by_parent = 1 THEN 1 END) as picked_by_parent,
        COUNT(CASE WHEN picked_by_parent = 0 AND check_out IS NOT NULL THEN 1 END) as picked_by_others
    FROM attendance 
    WHERE date = '$today'
")->fetch_assoc();

// Get first check-in today
$first_checkin = $conn->query("
    SELECT c.name, a.check_in 
    FROM attendance a
    JOIN children c ON a.child_id = c.id
    WHERE a.date = '$today' AND a.check_in IS NOT NULL
    ORDER BY a.check_in ASC
    LIMIT 1
")->fetch_assoc();

// Get last check-out today
$last_checkout = $conn->query("
    SELECT c.name, a.check_out 
    FROM attendance a
    JOIN children c ON a.child_id = c.id
    WHERE a.date = '$today' AND a.check_out IS NOT NULL
    ORDER BY a.check_out DESC
    LIMIT 1
")->fetch_assoc();

// Get attendance summary for date range
$attendance_summary = $conn->query("
    SELECT 
        date,
        COUNT(*) as present,
        COUNT(CASE WHEN check_out IS NOT NULL THEN 1 END) as checked_out,
        COUNT(CASE WHEN picked_by_parent = 1 THEN 1 END) as picked_by_parent,
        COUNT(CASE WHEN picked_by_parent = 0 AND check_out IS NOT NULL THEN 1 END) as picked_by_others
    FROM attendance 
    WHERE date BETWEEN '$start_date' AND '$end_date'
    GROUP BY date
    ORDER BY date DESC
");
?>

<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">System Reports</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="export_excel.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
               class="btn btn-sm btn-success me-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Children</h5>
                    <h2 class="mb-0"><?= $total_children ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Paid Fees</h5>
                    <h2 class="mb-0"><?= $payment_summary['paid_count'] ?></h2>
                    <small>KSh <?= number_format($payment_summary['total_amount'], 2) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Unpaid Fees</h5>
                    <h2 class="mb-0"><?= $payment_summary['not_paid_count'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Today's Attendance</h5>
                    <h2 class="mb-0"><?= $today_stats['checked_in'] ?? 0 ?></h2>
                    <small><?= $today_stats['checked_out'] ?? 0 ?> checked out</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Activity -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Today's Activity (<?= date('D, M j, Y') ?>)</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            First Check-in
                            <span class="badge bg-primary rounded-pill">
                                <?= $first_checkin ? $first_checkin['name'].' at '.date('h:i A', strtotime($first_checkin['check_in'])) : 'None' ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Last Check-out
                            <span class="badge bg-primary rounded-pill">
                                <?= $last_checkout ? $last_checkout['name'].' at '.date('h:i A', strtotime($last_checkout['check_out'])) : 'None' ?>
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Picked by Parents
                            <span class="badge bg-success rounded-pill"><?= $today_stats['picked_by_parent'] ?? 0 ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Picked by Others
                            <span class="badge bg-warning rounded-pill"><?= $today_stats['picked_by_others'] ?? 0 ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Breakdown -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Payment Breakdown</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?= $total_children > 0 ? ($payment_summary['paid_count']/$total_children)*100 : 0 ?>%" 
                             aria-valuenow="<?= $payment_summary['paid_count'] ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="<?= $total_children ?>">
                            <?= $payment_summary['paid_count'] ?> Paid (<?= $total_children > 0 ? round(($payment_summary['paid_count']/$total_children)*100) : 0 ?>%)
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Cash Payments
                            <span class="badge bg-info rounded-pill">KSh <?= number_format($payment_summary['cash_total'], 2) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            MPESA Payments
                            <span class="badge bg-success rounded-pill">KSh <?= number_format($payment_summary['mpesa_total'], 2) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Date Range Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="reports.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Summary Table -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Summary (<?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?>)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Present</th>
                            <th>Checked Out</th>
                            <th>Picked by Parents</th>
                            <th>Picked by Others</th>
                            <th>Attendance Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $attendance_summary->fetch_assoc()): 
                            $attendance_rate = $total_children > 0 ? round(($row['present'] / $total_children) * 100) : 0;
                        ?>
                        <tr>
                            <td><?= date('D, M j, Y', strtotime($row['date'])) ?></td>
                            <td><?= $row['present'] ?></td>
                            <td><?= $row['checked_out'] ?></td>
                            <td><?= $row['picked_by_parent'] ?></td>
                            <td><?= $row['picked_by_others'] ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $attendance_rate ?>%" 
                                         aria-valuenow="<?= $attendance_rate ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= $attendance_rate ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="attendance.php?date=<?= $row['date'] ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-list-check"></i> Details
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>