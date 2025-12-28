<?php
session_start();

if(isset($_SESSION['login_id'])) {
    unset($_SESSION['login_id']);
    unset($_SESSION['login_name']);
}

if(isset($_SESSION['login_user_id'])) {
    unset($_SESSION['login_user_id']);
    unset($_SESSION['login_user_name']);
}

session_destroy();

header("location: admin/login.php"); 
exit;
?>