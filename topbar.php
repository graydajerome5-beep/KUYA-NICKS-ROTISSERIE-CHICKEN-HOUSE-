<?php
if (!isset($_SESSION)) {
    session_start();
}

$system_name = $_SESSION['setting_name'] ?? "System";

$firstname = trim($_SESSION['login_firstname'] ?? "");
$lastname  = trim($_SESSION['login_lastname'] ?? "");
$username  = trim($_SESSION['login_username'] ?? "");

if (!empty($firstname) && !empty($lastname)) {
    $display_name = $firstname . " " . $lastname;
} elseif (!empty($firstname)) {
    $display_name = $firstname;
} elseif (!empty($lastname)) {
    $display_name = $lastname;
} elseif (!empty($username)) {
    $display_name = $username;
} else {
    $display_name = "Guest";
}
?>

<style>
    .logo {
        margin: auto;
        font-size: 20px;
        background: white;
        padding: 5px 11px;
        border-radius: 50%;
        color: #000000b3;
        box-shadow: 0 0 5px #ccc;
    }

    nav.navbar {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .top-user {
        font-weight: 600;
        margin-right: 5px;
    }
</style>

<nav class="navbar navbar-light bg-light fixed-top" style="padding:0;height:3.4em">
    <div class="container-fluid mt-2 mb-2">
        <div class="col-lg-12">

            <div class="col-md-1 float-left" style="display: flex;">
                <div class="logo"></div>
            </div>

            <div class="col-md-4 float-left">
                <large style="font-family: 'Dancing Script', cursive !important;">
                    <b><?php echo $system_name; ?> - Admin Site</b>
                </large>
            </div>

            <div class="col-md-2 float-right text-right">
                <a href="../logout.php" class="text-dark">
                    <span class="top-user"><?php echo $display_name; ?></span>
                    <i class="fa fa-sign-out-alt"></i>
                </a>
            </div>

        </div>
    </div>
</nav>
