<?php
session_start();
include('../includes/auth.php');
include('../includes/db.php');

$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch data
$result = $conn->query("
    SELECT c.name, c.class, a.date, a.check_in, a.check_out
    FROM attendance a
    JOIN children c ON a.child_id = c.id
    WHERE a.date BETWEEN '$start_date' AND '$end_date'
    ORDER BY a.date, c.class, c.name
");

// Excel headers
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="attendance_report_'.date('Ymd').'.xls"');

// Excel content
echo "Name\tClass\tDate\tCheck-In\tCheck-Out\n";
while ($row = $result->fetch_assoc()) {
    echo implode("\t", [
        $row['name'],
        $row['class'],
        $row['date'],
        $row['check_in'],
        $row['check_out']
    ]) . "\n";
}
exit;