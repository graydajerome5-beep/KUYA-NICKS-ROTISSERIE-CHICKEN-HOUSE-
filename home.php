<?php
include 'db_connect.php'; 

function clean_date($d){
    $d = trim($d);
    if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)) return $d;
    return false;
}

$range = isset($_GET['range']) ? $_GET['range'] : '7days';
$start = $end = date('Y-m-d');

if($range === 'today'){
    $start = $end = date('Y-m-d');
} elseif($range === 'week'){
    $start = date('Y-m-d', strtotime('-6 days'));
    $end = date('Y-m-d');
} elseif($range === 'month'){
    $start = date('Y-m-01');
    $end = date('Y-m-d');
} elseif($range === 'custom' && isset($_GET['from']) && isset($_GET['to'])){
    $f = clean_date($_GET['from']);
    $t = clean_date($_GET['to']);
    if($f && $t && $f <= $t){
        $start = $f;
        $end = $t;
    } else {
        $start = date('Y-m-d', strtotime('-6 days'));
        $end = date('Y-m-d');
    }
} else {
    $start = date('Y-m-d', strtotime('-6 days'));
    $end = date('Y-m-d');
}

$labels = [];
$period = new DatePeriod(
    new DateTime($start),
    new DateInterval('P1D'),
    (new DateTime($end))->modify('+1 day')
);
foreach($period as $dt){
    $labels[] = $dt->format('Y-m-d');
}

$orders_data = [];
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM `orders` WHERE DATE(`date_created`) = ?");
foreach($labels as $d){
    $stmt->bind_param("s", $d);
    $stmt->execute();
    $q = $stmt->get_result();
    $row = $q ? $q->fetch_assoc() : ['total'=>0];
    $orders_data[] = (int)$row['total'];
}
$stmt->close();

$menu_a = $conn->query("SELECT * FROM `product_list` WHERE `status` = 1")->num_rows;
$menu_i = $conn->query("SELECT * FROM `product_list` WHERE `status` = 0")->num_rows;
$o_queued = $conn->query("SELECT * FROM `orders` WHERE `status` = 0")->num_rows;
$o_cooking = $conn->query("SELECT * FROM `orders` WHERE `status` = 1")->num_rows;
$o_ready = $conn->query("SELECT * FROM `orders` WHERE `status` = 2")->num_rows;
$o_completed = $conn->query("SELECT * FROM `orders` WHERE `status` = 4")->num_rows;

$sales_query = $conn->prepare("
    SELECT SUM(ol.qty * pl.price) AS total_revenue
    FROM orders o
    JOIN order_list ol ON o.id = ol.order_id
    JOIN product_list pl ON ol.product_id = pl.id
    WHERE DATE(o.date_created) BETWEEN ? AND ? 
    AND o.status = 4 
");
$sales_query->bind_param("ss", $start, $end);
$sales_query->execute();
$sales_result = $sales_query->get_result();
$total_sales_range = $sales_result->fetch_assoc()['total_revenue'] ?? 0;
$sales_query->close();
?>

<style>
.containe-fluid {
    background-color: #f8f9fa !important; 
    min-height: 100vh;
    padding-top: 20px;
}

.card-stats {
    transition: all 0.4s ease-in-out;
    border-radius: 0.75rem !important;
    position: relative;
    overflow: hidden;
    color: white;
    min-height: 140px; 
    height: 100%; 
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.card-stats:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.25) !important;
}

.card-green { background: linear-gradient(45deg, #1c7430, #28a745); } 
.card-orange { background: linear-gradient(45deg, #e0a800, #ffc107); } 
.card-red { background: linear-gradient(45deg, #c82333, #dc3545); } 
.card-blue { background: linear-gradient(45deg, #0069d9, #007bff); } 
.card-success { background: linear-gradient(45deg, #218838, #28a745); } 
.card-purple { background: linear-gradient(45deg, #6f42c1, #8000ff); } 
.card-darkblue { background: linear-gradient(45deg, #004c99, #007bff); } 

.card-stats h5 {
    font-weight: 500;
    margin-bottom: 0.5rem; 
    font-size: 1rem;
    opacity: 0.8;
}
.card-stats .card-title {
    font-size: 2.2rem;
    font-weight: 700;
    margin-top: 0;
}
.card-stats .icon-overlay {
    position: absolute;
    top:50%;
    right:15px;
    transform: translateY(-50%);
    font-size:4.5em; 
    opacity:0.25; 
}
.filter-row {
    margin: 1rem 0;
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
    background-color: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.filter-row input[type="date"],
.filter-row select,
.filter-row button {
    padding: 9px 15px; 
    border-radius: 6px;
    border: 1px solid #ced4da;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.filter-row button[type="submit"] {
    background: linear-gradient(90deg,#007bff,#0056b3);
    color: white;
    border: none;
}
.filter-row button[type="button"] { 
    background: #6c757d;
    color: white;
    border: none;
}
.filter-row button:hover {
    filter: brightness(1.1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.filter-row select:hover,
.filter-row input[type="date"]:hover {
    border-color: #007bff;
    box-shadow: 0 3px 8px rgba(0,123,255,0.2);
}

.chart-container {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    padding: 20px;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <div class="containe-fluid">
    <div class="row mt-3 ml-3 mr-3 mb-4">
        <div class="col-lg-12">
            <div class="card rounded-0 shadow-sm border-0 bg-white">
                <div class="card-body">
                    <h4 class="mb-0"><i class="fas fa-tachometer-alt"></i> Administrator Dashboard</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row m-3">
        
        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-stats card-green shadow border-0">
                <div class="card-body">
                    <h5>Active Menu</h5>
                    <h2 class="card-title text-right"><b><?= number_format($menu_a) ?></b></h2>
                    <i class="fas fa-utensils icon-overlay"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-stats card-orange shadow border-0">
                <div class="card-body">
                    <h5>Inactive Menu</h5>
                    <h2 class="card-title text-right"><b><?= number_format($menu_i) ?></b></h2>
                    <i class="fas fa-archive icon-overlay"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-stats card-red shadow border-0">
                <div class="card-body">
                    <h5>Orders Queued (0)</h5>
                    <h2 class="card-title text-right"><b><?= number_format($o_queued) ?></b></h2>
                    <i class="fas fa-hourglass-start icon-overlay"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-stats card-blue shadow border-0">
                <div class="card-body">
                    <h5>Orders Cooking (1)</h5>
                    <h2 class="card-title text-right"><b><?= number_format($o_cooking) ?></b></h2>
                    <i class="fas fa-fire-alt icon-overlay"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-stats card-success shadow border-0">
                <div class="card-body">
                    <h5>Orders Ready (2)</h5>
                    <h2 class="card-title text-right"><b><?= number_format($o_ready) ?></b></h2>
                    <i class="fas fa-concierge-bell icon-overlay"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-stats card-purple shadow border-0">
                <div class="card-body">
                    <h5>Orders Completed (4)</h5>
                    <h2 class="card-title text-right"><b><?= number_format($o_completed) ?></b></h2>
                    <i class="fas fa-check-double icon-overlay"></i>
                </div>
            </div>
        </div>

    </div>
    
    <div class="row m-3">
        <div class="col-lg-12 mb-3">
            <div class="card card-stats card-darkblue shadow border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h5>Total Sales Revenue (<?php echo date('M d, Y', strtotime($start)) . ' to ' . date('M d, Y', strtotime($end)); ?>)</h5>
                            <h2 class="card-title"><b>₱<?= number_format($total_sales_range, 2) ?></b></h2>
                        </div>
                        <div class="col-3 text-right">
                            <i class="fas fa-money-bill-wave icon-overlay" style="opacity: 0.5; font-size: 5em;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row m-3">
        <div class="col-lg-12">
            <form method="get" class="filter-row">
                <input type="hidden" name="page" value="home">
                <label><i class="fas fa-calendar-alt mr-1"></i> Range:</label>
                <select name="range" id="range" onchange="this.form.submit()">
                    <option value="7days" <?= $range=='7days' ? 'selected' : '' ?>>Last 7 days</option>
                    <option value="today" <?= $range=='today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $range=='week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $range=='month' ? 'selected' : '' ?>>This Month</option>
                    <option value="custom" <?= $range=='custom' ? 'selected' : '' ?>>Custom</option>
                </select>

                <label>From:</label>
                <input type="date" name="from" value="<?php echo isset($_GET['from']) ? htmlspecialchars($_GET['from']) : $start; ?>" id="date-from">

                <label>To:</label>
                <input type="date" name="to" value="<?php echo isset($_GET['to']) ? htmlspecialchars($_GET['to']) : $end; ?>" id="date-to">

                <button type="submit"><i class="fas fa-filter"></i> Apply</button>
                <button type="button" onclick="window.location='?page=home';"><i class="fas fa-redo"></i> Reset</button>
            </form>
        </div>
    </div>

    <div class="row m-3">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-body chart-container">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Orders Overview: <?php echo date('M d, Y', strtotime($start)) . ' to ' . date('M d, Y', strtotime($end)); ?></h5>
                    <canvas id="ordersBarLine" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function toggleCustomDates() {
    const range = document.getElementById('range').value;
    const from = document.getElementById('date-from');
    const to = document.getElementById('date-to');
    const applyButton = document.querySelector('.filter-row button[type="submit"]');

    if (range === 'custom') {
        from.style.display = 'inline-block';
        to.style.display = 'inline-block';
        applyButton.style.display = 'inline-block';
    } else {
        from.style.display = 'none';
        to.style.display = 'none';
        if (event && event.type === 'change') {
            document.querySelector('.filter-row form').submit();
        }
    }
}

document.addEventListener('DOMContentLoaded', toggleCustomDates);
document.getElementById('range').addEventListener('change', toggleCustomDates);


const labels = <?php echo json_encode($labels); ?>;
const ordersData = <?php echo json_encode($orders_data); ?>;

const ctx = document.getElementById('ordersBarLine').getContext('2d');

const gradientBar = ctx.createLinearGradient(0,0,0,400);
gradientBar.addColorStop(0,'rgba(76,175,80,0.9)');
gradientBar.addColorStop(1,'rgba(76,175,80,0.3)');

const gradientLine = ctx.createLinearGradient(0,0,0,400);
gradientLine.addColorStop(0,'rgba(33,150,243,0.6)');
gradientLine.addColorStop(1,'rgba(33,150,243,0.05)');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                type: 'bar',
                label: 'Orders per Day',
                data: ordersData,
                backgroundColor: gradientBar,
                borderColor: 'rgba(56,142,60,1)',
                borderWidth: 1,
                borderRadius: 5
            },
            {
                type: 'line',
                label: 'Orders Trend',
                data: ordersData,
                borderColor: 'rgba(33,150,243,1)',
                backgroundColor: gradientLine,
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(33,150,243,1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 7
            }
        ]
    },
    options: {
        responsive: true,
        animation: { duration: 1000, easing: 'easeOutQuart' },
        plugins: {
            legend: { position: 'top', labels:{ font:{ size:14, weight:'600' } } },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: '#333',
                titleColor: '#fff',
                bodyColor: '#fff',
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 }
            }
        },
        interaction: { mode: 'index', intersect: false },
        scales:{
            x: { grid:{ display:false }, ticks:{ font:{ size:13 } } },
            y: { beginAtZero:true, ticks:{ precision:0, font:{ size:13 } }, grid:{ color:'rgba(0,0,0,0.05)' } }
        }
    }
});
</script>

<?php
if(isset($conn) && $conn){
    $conn->close();
}
?>