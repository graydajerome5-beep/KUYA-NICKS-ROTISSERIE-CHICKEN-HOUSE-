<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fos_db";

date_default_timezone_set('Asia/Manila');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>