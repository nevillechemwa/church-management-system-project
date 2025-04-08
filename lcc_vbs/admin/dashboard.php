<?php
require_once('../includes/auth.php');
require_once('../includes/header.php');

// Get stats
$total_children = $conn->query("SELECT COUNT(*) FROM children WHERE is_active = TRUE")->fetch_row()[0];
$today_attendance = $conn->query("SELECT COUNT(DISTINCT child_id) FROM attendance WHERE date = CURDATE()")->fetch_row()[0];
$total_admins = $conn->query("SELECT COUNT(*) FROM admins")->fetch_row()[0];
$attendance_rate = $total_children > 0 ? round(($today_attendance/$total_children)*100) : 0;

// Weekly attendance data - complete 7 days with zeros for missing days
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $formatted_date = date('M j', strtotime($date));
    
    // Check if we have data for this date
    $result = $conn->query("
        SELECT COUNT(DISTINCT child_id) AS count 
        FROM attendance 
        WHERE date = '$date'
    ")->fetch_assoc();
    
    $weekly_data[] = [
        'day' => $day_name,
        'date' => $formatted_date,
        'count' => $result['count'] ?? 0
    ];
}

// Class-wise attendance
$class_attendance = $conn->query("
    SELECT 
        c.class,
        COUNT(DISTINCT a.child_id) AS attended,
        (SELECT COUNT(*) FROM children WHERE class = c.class AND is_active = TRUE) AS total
    FROM children c
    LEFT JOIN attendance a ON c.id = a.child_id AND a.date = CURDATE()
    WHERE c.is_active = TRUE
    GROUP BY c.class
    ORDER BY c.class
")->fetch_all(MYSQLI_ASSOC);

// Recent activities
$activities = $conn->query("
    (SELECT 'child' as type, id, name, registration_date as activity_date FROM children ORDER BY registration_date DESC LIMIT 3)
    UNION
    (SELECT 'attendance' as type, child_id as id, CONCAT('Attendance marked for child ID: ', child_id) as name, date as activity_date FROM attendance ORDER BY date DESC LIMIT 3)
    UNION
    (SELECT 'admin' as type, id, username as name, created_at as activity_date FROM admins ORDER BY created_at DESC LIMIT 3)
    ORDER BY activity_date DESC LIMIT 5
");
?>

<main class="col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard Overview</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="register_child.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-person-plus"></i> Register Child
                </a>
                <a href="attendance.php" class="btn btn-sm btn-success">
                    <i class="bi bi-clipboard-check"></i> Mark Attendance
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Registered Children</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_children ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="manage_children.php" class="small text-primary stretched-link">View all children</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Today's Attendance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $today_attendance ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="attendance.php" class="small text-success stretched-link">View attendance</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Admin Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_admins ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-lock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="manage_admins.php" class="small text-info stretched-link">Manage admins</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Attendance Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $attendance_rate ?>%</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="reports.php" class="small text-warning stretched-link">View reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Row -->
    <div class="row mb-4">
        <!-- Weekly Attendance Trend -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Weekly Attendance Trend (Last 7 Days)</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow">
                            <a class="dropdown-item" href="reports.php?range=weekly">View Full Report</a>
                        </div>
                    </div>
                </div>
                <div class="card-body" style="height: 300px; overflow: hidden;">
                    <canvas id="attendanceChart" height="300" style="display: block; box-sizing: border-box; height: 300px; width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <!-- Today's Attendance by Class -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Attendance by Class</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow">
                            <a class="dropdown-item" href="reports.php?type=class">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Attended</th>
                                    <th>Total</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_attendance as $class): ?>
                                <tr>
                                    <td><?= htmlspecialchars($class['class']) ?></td>
                                    <td><?= $class['attended'] ?></td>
                                    <td><?= $class['total'] ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?= ($class['attended']/$class['total'])*100 > 50 ? 'success' : 'warning' ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $class['total'] > 0 ? round(($class['attended']/$class['total'])*100) : 0 ?>%" 
                                                 aria-valuenow="<?= $class['total'] > 0 ? round(($class['attended']/$class['total'])*100) : 0 ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= $class['total'] > 0 ? round(($class['attended']/$class['total'])*100) : 0 ?>%
                                            </div>
                                        </div>
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

    <!-- Quick Actions & Recent Activities Row -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="register_child.php" class="btn btn-primary w-100 py-3 d-flex flex-column align-items-center">
                                <i class="bi bi-person-plus fs-3 mb-2"></i>
                                Register Child
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="attendance.php" class="btn btn-success w-100 py-3 d-flex flex-column align-items-center">
                                <i class="bi bi-clipboard-check fs-3 mb-2"></i>
                                Mark Attendance
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="manage_children.php" class="btn btn-info w-100 py-3 d-flex flex-column align-items-center">
                                <i class="bi bi-people fs-3 mb-2"></i>
                                Manage Children
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="reports.php" class="btn btn-warning w-100 py-3 d-flex flex-column align-items-center">
                                <i class="bi bi-file-earmark-bar-graph fs-3 mb-2"></i>
                                Generate Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                    <a href="activity_log.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php while ($activity = $activities->fetch_assoc()): ?>
                            <div class="activity-item mb-3">
                                <div class="d-flex justify-content-between">
                                    <p class="mb-1">
                                        <?php
                                        switch($activity['type']) {
                                            case 'child':
                                                echo '<i class="bi bi-person-plus text-success me-2"></i>New child registered: ';
                                                break;
                                            case 'attendance':
                                                echo '<i class="bi bi-clipboard-check text-primary me-2"></i>Attendance marked ';
                                                echo htmlspecialchars($activity['name']); // Contains child ID
                                                break;
                                            case 'admin':
                                                echo '<i class="bi bi-shield-plus text-info me-2"></i>New admin added: ';
                                                break;
                                        }
                                        echo htmlspecialchars($activity['name']);
                                        ?>
                                    </p>
                                    <small class="text-muted">
                                        <?= date('M j, g:i a', strtotime($activity['activity_date'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Weekly Attendance Chart - Fixed Version
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($weekly_data, 'day')) ?>,
            datasets: [{
                label: 'Attendance Count',
                data: <?= json_encode(array_column($weekly_data, 'count')) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                barPercentage: 0.6,
                categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        stepSize: 1
                    },
                    grid: {
                        display: true,
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return <?= json_encode(array_column($weekly_data, 'date')) ?>[context[0].dataIndex];
                        },
                        label: function(context) {
                            return 'Attendance: ' + context.raw;
                        }
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Mobile sidebar toggle
    document.querySelector('.navbar-toggler').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
        document.body.classList.toggle('offcanvas-backdrop');
    });
});

// Smooth delete confirmation
function confirmDelete(id, username) {
    Swal.fire({
        title: 'Delete Admin?',
        text: `Are you sure you want to delete ${username}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `manage_admins.php?delete=${id}`;
        }
    });
}
</script>