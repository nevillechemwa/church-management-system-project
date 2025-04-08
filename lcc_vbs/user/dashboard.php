<?php
require_once('header.php');

// Get stats
$total_children = $conn->query("SELECT COUNT(*) FROM children WHERE is_active = TRUE")->fetch_row()[0];
$today_attendance = $conn->query("SELECT COUNT(DISTINCT child_id) FROM attendance WHERE date = CURDATE()")->fetch_row()[0];
$today_checkouts = $conn->query("SELECT COUNT(DISTINCT child_id) FROM attendance WHERE date = CURDATE() AND check_out IS NOT NULL")->fetch_row()[0];
$attendance_rate = $total_children > 0 ? round(($today_attendance / $total_children) * 100) : 0;

// Weekly attendance data for chart
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $count = $conn->query("SELECT COUNT(DISTINCT child_id) FROM attendance WHERE date = '$date'")->fetch_row()[0];
    $weekly_data[] = [
        'day' => $day_name,
        'count' => $count,
        'date' => date('M j', strtotime($date))
    ];
}

// Class-wise attendance
$class_attendance = $conn->query("
    SELECT c.class, COUNT(a.child_id) as attended 
    FROM children c
    LEFT JOIN attendance a ON c.id = a.child_id AND a.date = CURDATE()
    WHERE c.is_active = TRUE
    GROUP BY c.class
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Registered Children</h5>
            </div>
            <div class="card-body text-center">
                <h1 class="display-4"><?= $total_children ?></h1>
                <p class="text-muted">Total active children</p>
                <a href="register_child.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Register New Child
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Today's Attendance</h5>
            </div>
            <div class="card-body text-center">
                <h1 class="display-4"><?= $today_attendance ?></h1>
                <div class="progress mt-3" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $attendance_rate ?>%" 
                         aria-valuenow="<?= $attendance_rate ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= $attendance_rate ?>%
                    </div>
                </div>
                <a href="attendance.php" class="btn btn-success mt-3">
                    <i class="bi bi-clipboard-check"></i> Mark Attendance
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Checked Out Today</h5>
            </div>
            <div class="card-body text-center">
                <h1 class="display-4"><?= $today_checkouts ?></h1>
                <p class="text-muted"><?= $today_attendance > 0 ? round(($today_checkouts/$today_attendance)*100) : 0 ?>% of attendees</p>
                <a href="attendance.php" class="btn btn-info">
                    <i class="bi bi-people"></i> View All
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Weekly Attendance Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Weekly Attendance Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Class-wise Attendance -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Today's Attendance by Class</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Attended</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($class_attendance as $class): ?>
                            <tr>
                                <td><?= htmlspecialchars($class['class']) ?></td>
                                <td><?= $class['attended'] ?></td>
                                <td>
                                    <?php 
                                    $class_total = $conn->query("SELECT COUNT(*) FROM children WHERE class = '{$class['class']}' AND is_active = TRUE")->fetch_row()[0];
                                    echo $class_total > 0 ? round(($class['attended']/$class_total)*100) : 0;
                                    ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Check-Ins</h5>
            </div>
            <div class="card-body">
                <?php
                $recent = $conn->query("
                    SELECT c.name, c.class, a.check_in, a.dropped_by, a.dropped_by_phone
                    FROM attendance a
                    JOIN children c ON a.child_id = c.id
                    WHERE a.date = CURDATE()
                    ORDER BY a.check_in DESC
                    LIMIT 5
                ")->fetch_all(MYSQLI_ASSOC);
                ?>
                
                <?php if (count($recent) > 0): ?>
                <div class="list-group">
                    <?php foreach ($recent as $child): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($child['name']) ?> (<?= htmlspecialchars($child['class']) ?>)</h6>
                            <small><?= date('h:i A', strtotime($child['check_in'])) ?></small>
                        </div>
                        <small class="text-muted">By <?= htmlspecialchars($child['dropped_by']) ?> (<?= htmlspecialchars($child['dropped_by_phone']) ?>)</small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No check-ins recorded yet today</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../assets/js/common.js"></script>
<script>
$(document).ready(function() {
    // Sidebar toggle for all pages
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });
    
    // Weekly attendance chart
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($weekly_data, 'date')) ?>,
            datasets: [{
                label: 'Attendance',
                data: <?= json_encode(array_column($weekly_data, 'count')) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

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

<?php include('../includes/footer.php'); ?>