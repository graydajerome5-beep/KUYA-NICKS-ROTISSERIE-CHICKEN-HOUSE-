<nav id="sidebar" class='mx-lt5 bg-dark' >
    
    <div class="sidebar-list">

        <a href="index.php?page=home" class="nav-item nav-home"><span class='icon-field'><i class="fa fa-home"></i></span> Dashboard</a>
        <a href="index.php?page=orders" class="nav-item nav-orders"><span class='icon-field'><i class="fa fa-list"></i></span> Orders</a>

        <a href="index.php?page=pos" class="nav-item nav-pos"><span class='icon-field'><i class="fa fa-calculator"></i></span> Walk-In POS</a>
        
        <a href="index.php?page=menu" class="nav-item nav-menu"><span class='icon-field'><i class="fa fa-list"></i></span> Menu</a>
        <a href="index.php?page=categories" class="nav-item nav-categories"><span class='icon-field'><i class="fa fa-list"></i></span> Category List</a>
        
        <a href="index.php?page=feedback_report" class="nav-item nav-feedback_report"><span class='icon-field'><i class="fa fa-comments"></i></span> Customer Feedback</a>
        
        <a href="index.php?page=sales_report" class="nav-item nav-sales_report"><span class='icon-field'><i class="fa fa-chart-line"></i></span> Sales Report</a>
        
        <?php if($_SESSION['login_type'] == 1): ?>
        <a href="index.php?page=users" class="nav-item nav-users"><span class='icon-field'><i class="fa fa-users"></i></span> Users</a>
        <a href="index.php?page=site_settings" class="nav-item nav-site_settings"><span class='icon-field'><i class="fa fa-cogs"></i></span> Site Settings</a>
        <?php endif; ?>
    </div>

</nav>
<script>
	$('.nav-<?php echo isset($_GET['page']) ? $_GET['page'] : '' ?>').addClass('active')
</script>
