<?php
require_once('header.php');

// Show messages
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['success']).'</div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
    unset($_SESSION['error']);
}

$date = $_GET['date'] ?? date('Y-m-d');
$attendance = $conn->query("
    SELECT 
        c.id, 
        c.name, 
        c.class, 
        a.check_in, 
        a.check_out,
        a.dropped_by,
        a.dropped_by_phone,
        a.dropped_by_parent,
        a.picked_by,
        a.picked_by_phone,
        a.picked_by_parent
    FROM children c
    LEFT JOIN attendance a ON c.id = a.child_id AND a.date = '$date'
    WHERE c.is_active = TRUE
    ORDER BY c.class, c.name
");
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Daily Attendance - <?= date('F j, Y', strtotime($date)) ?></h5>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <input type="date" name="date" value="<?= $date ?>" class="form-control">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> View Date
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $attendance->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                        <td>
                            <?php if ($row['check_in']): ?>
                                <?= date('h:i A', strtotime($row['check_in'])) ?>
                                <br><small class="text-muted">By <?= 
                                    $row['dropped_by_parent'] ? 'Parent' : htmlspecialchars($row['dropped_by']) 
                                    ?> (<?= htmlspecialchars($row['dropped_by_phone']) ?>)
                                </small>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['check_out']): ?>
                                <?= date('h:i A', strtotime($row['check_out'])) ?>
                                <br><small class="text-muted">By <?= 
                                    $row['picked_by_parent'] ? 'Parent' : htmlspecialchars($row['picked_by']) 
                                    ?> (<?= htmlspecialchars($row['picked_by_phone']) ?>)
                                </small>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['check_in'] && $row['check_out']): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php elseif ($row['check_in']): ?>
                                <span class="badge bg-warning">Checked In</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Not Checked In</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$row['check_in']): ?>
                                <a href="mark_attendance.php?child_id=<?= $row['id'] ?>&action=check_in&date=<?= $date ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="bi bi-box-arrow-in-right"></i> Check In
                                </a>
                            <?php elseif (!$row['check_out']): ?>
                                <a href="mark_attendance.php?child_id=<?= $row['id'] ?>&action=check_out&date=<?= $date ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="bi bi-box-arrow-right"></i> Check Out
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
    // Make sure this is included in all pages
$(document).ready(function() {
    // Sidebar toggle for all pages
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });
    
    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove(); 
        });
    }, 5000);
});
</script>