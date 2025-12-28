<?php
include 'admin/db_connect.php'; 

$order_id = isset($_GET['id']) ? $_GET['id'] : 0;
$status_names = [
    0 => 'Queued', 1 => 'Cooking', 2 => 'Ready for Pickup', 
    3 => 'Canceled', 4 => 'Order Received', 5 => 'Canceled'
];
$status_classes = [
    0 => 'badge-warning', 1 => 'badge-info', 2 => 'badge-success', 
    3 => 'badge-danger', 4 => 'badge-primary', 5 => 'badge-danger'
];

header('Content-Type: application/json');

if ($order_id > 0) {
    $qry = $conn->query("SELECT status FROM orders WHERE id = '$order_id'");
    if ($qry && $qry->num_rows > 0) {
        $row = $qry->fetch_assoc();
        $status = $row['status'];

        $badge_html = '<span class="badge ' . $status_classes[$status] . '">' . $status_names[$status] . '</span>';
        
        $output = ['status_id' => $status, 'html' => $badge_html];
        echo json_encode($output);
        exit;
    }
}
echo json_encode(['status_id' => -1, 'html' => '']); 
?>