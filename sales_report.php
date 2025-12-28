<?php

include 'db_connect.php';
$chart_labels = [];
$chart_data = [];
$pie_data_list = [];
$table_data = [];

$filter_type = isset($_POST['filter']) ? $_POST['filter'] : 'daily';

$sales_type = isset($_POST['sales_type']) ? $_POST['sales_type'] : 'all_types';

$date_input = isset($_POST['date_input']) ? $_POST['date_input'] : date('Y-m-d');
$date_to = date('Y-m-d');

$sql_date_filter = "";
$sql_type_filter = "";
$date_label = "";
$sales_type_label = ""; 

switch ($filter_type) {
    case 'daily':
        $date_for_filter = date("Y-m-d", strtotime($date_input));
        $sql_date_filter = " DATE(s.date_created) = '{$date_for_filter}' "; 
        $date_label = date("F d, Y", strtotime($date_for_filter));
        break;
    case 'monthly':
        $year_month = date("Y-m", strtotime($date_input));
        $sql_date_filter = " DATE_FORMAT(s.date_created, '%Y-%m') = '{$year_month}' "; 
        $date_label = date("F Y", strtotime($date_input));
        break;
    case 'yearly':
        $year = date("Y", strtotime($date_input));
        $sql_date_filter = " YEAR(s.date_created) = '{$year}' "; 
        $date_label = "Year " . $year;
        break;
    case 'all':
    default:
        $sql_date_filter = "";
        $date_label = "";
        break;
}

if ($sales_type == 'online') {
    $sql_type_filter = " EXISTS (SELECT 1 FROM `orders` o WHERE o.id = s.order_id AND o.user_id IS NOT NULL AND o.user_id <> 0) ";
    $sales_type_label = " (Online Sales)";

} elseif ($sales_type == 'walkin') {
    $sql_type_filter = " (
                            NOT EXISTS (SELECT 1 FROM `orders` o WHERE o.id = s.order_id) 
                            OR 
                            EXISTS (SELECT 1 FROM `orders` o WHERE o.id = s.order_id AND (o.user_id IS NULL OR o.user_id = 0))
                        ) ";
    $sales_type_label = " (Walk-in Sales)";
} else {
    $sql_type_filter = "";
    $sales_type_label = " (All Sales Types)";
}

$final_filter_clause = "";
$filters = [];

if (!empty($sql_date_filter)) {
    $filters[] = $sql_date_filter;
}
if (!empty($sql_type_filter)) {
    $filters[] = $sql_type_filter;
}

if (!empty($filters)) {
    $final_filter_clause = " WHERE " . implode(" AND ", $filters);
}

$query = "
    SELECT
        s.date_created AS sale_date,
        s.order_id AS order_id, 
        s.order_id AS transaction_code,
        ol.qty,
        p.price AS price, 
        p.name AS product_name
    FROM
        sales s 
    INNER JOIN
        order_list ol ON ol.order_id = s.order_id 
    INNER JOIN
        product_list p ON p.id = ol.product_id 
    {$final_filter_clause}
    ORDER BY
        s.date_created DESC, s.order_id DESC
";
$sales_query = $conn->query($query);
?>

<style>
@media print {
    .no-print, 
    .navbar, 
    .sidebar, 
    .main-header, 
    .main-sidebar, 
    .control-sidebar, 
    #filter-form,
    #print-btn-container,
    .brand-link,
    .nav-sidebar,
    .main-footer,
    .content-header { 
        display: none !important; 
    }

    .content-wrapper {
        margin-left: 0 !important;
        padding-top: 0 !important;
        float: none !important;
        width: 100% !important;
        min-height: auto !important; 
    }
    
    .content, 
    .container-fluid,
    body {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card, #sales-report-printable {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
    }
    
    .report-title {
        text-align: center;
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 1.5em;
    }

    .table-bordered thead th {
        background-color: #f8f9fa !important;
        color: #343a40 !important;
        -webkit-print-color-adjust: exact; 
        color-adjust: exact; 
    }
}

.table tfoot th {
    font-size: 1.1em;
    background-color: #f0f0f0;
}
.order-subtotal-row {
    background-color: #e9ecef;
    font-weight: bold;
}
</style>

<div class="container-fluid">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-body">
                
                <div class="row mb-3 no-print">
                    <div class="col-md-12">
                        <form id="filter-form" action="index.php?page=sales_report" method="POST" class="form-inline">
                            
                            <label for="filter" class="mr-2">Filter By Time:</label>
                            <select name="filter" id="filter" class="custom-select custom-select-sm mr-2" onchange="updateDateInput(this.value)">
                                <option value="daily" <?php echo $filter_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="monthly" <?php echo $filter_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                <option value="yearly" <?php echo $filter_type == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                
                            </select>

                            <label for="sales_type" class="mr-2 ml-4">Filter By Type:</label>
                            <select name="sales_type" id="sales_type" class="custom-select custom-select-sm mr-2">
                                <option value="all_types" <?php echo $sales_type == 'all_types' ? 'selected' : ''; ?>>All Sales Types</option>
                                <option value="online" <?php echo $sales_type == 'online' ? 'selected' : ''; ?>>Online Sales</option>
                                <option value="walkin" <?php echo $sales_type == 'walkin' ? 'selected' : ''; ?>>Walk-in Sales</option>
                            </select>
                            
                            <input type="date" name="date_input" id="date_input_field" class="form-control form-control-sm mr-2" 
                                    value="<?php echo $date_input; ?>" style="display: <?php echo $filter_type == 'all' ? 'none' : 'inline-block'; ?>;">
                                
                            <button type="submit" class="btn btn-primary btn-sm">Generate Report</button>
                        </form>
                    </div>
                </div>
                
                <hr class="no-print">

                <div class="row">
                    <div class="col-md-12 d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="d-inline-block report-title">Sales Report Summary</h2>
                            <span class="badge badge-primary d-inline-block align-top ml-2 mt-1"><?php echo $date_label . $sales_type_label; ?></span>
                        </div>
                        <div id="print-btn-container" class="no-print">
                            <button class="btn btn-success btn-lg" type="button" id="print-report-btn">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
                <hr>

                <div id="sales-report-printable">
                    <div class="row">
                        <div class="col-md-12">
                            <h4 class="mb-3">Sales Data by Transaction/Item</h4>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">Date</th>
                                        <th style="width: 10%;">Time</th>
                                        <th style="width: 15%;">Transaction ID (Order ID)</th>
                                        <th style="width: 35%;">Product Item</th>
                                        <th style="width: 10%;" class="text-center">Qty</th>
                                        <th style="width: 20%;" class="text-right">Amount (Subtotal)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $grand_total = 0;
                                    $current_order_id = null;
                                    $order_total = 0;
                                    
                                    $all_rows = $sales_query->fetch_all(MYSQLI_ASSOC);
                                    
                                    if (!$sales_query || empty($all_rows)) {
                                        echo "<tr><td colspan='6' class='text-center'>No sales data found for the selected period and type.</td></tr>";
                                    } else {
                                        
                                        $num_rows = count($all_rows);
                                        for ($i = 0; $i < $num_rows; $i++) {
                                            $row = $all_rows[$i];

                                            $item_subtotal = $row['price'] * $row['qty'];

                                            if ($row['order_id'] != $current_order_id) {
                                                if ($current_order_id !== null) {
                                                    
                                                    ?>
                                                    <tr class="order-subtotal-row">
                                                        <td colspan="5" class="text-right">Total for Transaction #<?php echo $prev_transaction_code; ?>:</td>
                                                        <td class="text-right">₱<?php echo number_format($order_total, 2); ?></td>
                                                    </tr>
                                                    <?php
                                                    $grand_total += $order_total;
                                                }
                                                
                                                $order_total = 0;
                                                $current_order_id = $row['order_id'];
                                                $prev_transaction_code = $row['transaction_code']; 
                                            }
                                            
                                            ?>
                                            <tr>
                                                <td><?php echo ($order_total == 0) ? date("M d, Y", strtotime($row['sale_date'])) : ''; ?></td>
                                                
                                                <td><?php echo ($order_total == 0) ? date("h:i A", strtotime($row['sale_date'])) : ''; ?></td>
                                                
                                                <td><?php echo ($order_total == 0) ? $row['transaction_code'] : ''; ?></td>
                                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                                <td class="text-center"><?php echo $row['qty']; ?></td>
                                                <td class="text-right">₱<?php echo number_format($item_subtotal, 2); ?></td>
                                            </tr>
                                            <?php
                                            $order_total += $item_subtotal; 
                                            
                                            if ($i == $num_rows - 1) {
                                                ?>
                                                <tr class="order-subtotal-row">
                                                    <td colspan="5" class="text-right">Total for Transaction #<?php echo $prev_transaction_code; ?>:</td>
                                                    <td class="text-right">₱<?php echo number_format($order_total, 2); ?></td>
                                                </tr>
                                                <?php
                                                $grand_total += $order_total;
                                            }
                                        }

                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-right">GRAND TOTAL for <?php echo $date_label . $sales_type_label; ?>:</th>
                                        <th class="text-right">₱<?php echo number_format($grand_total, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateDateInput(filter) {
        var dateInput = document.getElementById('date_input_field');
        
        if (filter === 'all') {
            dateInput.style.display = 'none';
            dateInput.required = false;
        } else {
            dateInput.style.display = 'inline-block';
            dateInput.required = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateDateInput(document.getElementById('filter').value);

        $('#print-report-btn').click(function(){
            window.print();
        });
        
    });
</script>