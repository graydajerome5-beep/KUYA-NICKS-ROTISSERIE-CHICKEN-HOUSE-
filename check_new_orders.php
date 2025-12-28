<?php
include 'admin/db_connect.php';

$qry = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 0");

$result = $qry->fetch_assoc();
$new_orders_count = $result['count'];
echo $new_orders_count;
?>