<?php
require_once '../../includes/check_auth.php';
check_auth(['admin', 'it_operation']);

require_once '../../includes/db.php';
$conn = $db->conn;

$type = $_GET['type'] ?? 'assets';
$format = $_GET['format'] ?? 'csv';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$department = $_GET['department'] ?? '';

// Fetch data based on type (similar to reports.php)
// ... (same query logic as reports.php)

// Generate filename
$filename = $type . '_report_' . date('Y-m-d') . '.' . $format;

if ($format == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers based on type
    switch($type) {
        case 'assets':
            fputcsv($output, ['Asset Tag', 'Asset Name', 'Class', 'Model', 'Serial Number', 'Status', 'Location', 'Department', 'Cost', 'Acquisition Date']);
            foreach ($report_data as $row) {
                fputcsv($output, [
                    $row['asset_tag'],
                    $row['asset_name'],
                    $row['class_name'],
                    $row['model'],
                    $row['serial_number'],
                    $row['asset_status'],
                    $row['location_name'],
                    $row['department_name'],
                    $row['cost'],
                    $row['acquisition_date']
                ]);
            }
            break;
        // Add other report types...
    }
    
    fclose($output);
} elseif ($format == 'pdf') {
    // You can implement PDF generation using libraries like TCPDF or Dompdf
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>