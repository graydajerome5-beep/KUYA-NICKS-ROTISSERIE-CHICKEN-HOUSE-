<?php
include 'db_connect.php';

$status_map = [
    0 => 'Queued',
    1 => 'Cooking',
    2 => 'Ready for Pickup',
    3 => 'Canceled',
    4 => 'Received',
    5 => 'Canceled'
];
/**
 * @param mysqli $conn Koneksyon sa database.
 * @param int|null $specific_order_id Kung may value, isang order lang ang ibabalik.
 */
function generate_order_rows($conn, $specific_order_id = null) {
    global $status_map; 

    $where = "";
    if ($specific_order_id !== null) {
        $order_id_safe = $conn->real_escape_string($specific_order_id);
        $where = "WHERE o.id = {$order_id_safe}";
    }
    
    $orders_query = $conn->query("
        SELECT  
            o.id AS order_id,
            o.status,
            o.date_created,
            o.proof_file,       
            u.first_name,  
            u.last_name,
            u.address,
            u.email,
            u.mobile,
            ol.qty,
            p.name AS product_name,
            p.price,
            (ol.qty * p.price) AS item_amount
        FROM  
            orders o
        LEFT JOIN  
            user_info u ON o.user_id = u.user_id
        LEFT JOIN
            order_list ol ON o.id = ol.order_id
        LEFT JOIN
            product_list p ON ol.product_id = p.id
        {$where}
        ORDER BY  
            FIELD(o.status, 0, 1, 2, 4, 3, 5), o.date_created DESC
    ");
    
    if ($orders_query === false) {
        return ['online_html' => '<tr><td colspan="7" class="text-center no-results">Error executing query.</td></tr>', 'walkin_html' => '<tr><td colspan="7" class="text-center no-results">Error executing query.</td></tr>', 'single_html' => ''];
    }

    $grouped_orders = [];
    while($row = $orders_query->fetch_assoc()) {
        $order_id = $row['order_id'];
        if (!isset($grouped_orders[$order_id])) {
            $grouped_orders[$order_id] = [
                'main' => $row,
                'products' => [],
                'proof_file' => $row['proof_file']
            ];
        }
        $grouped_orders[$order_id]['products'][] = [
            'name' => $row['product_name'],
            'qty' => $row['qty'],
            'price' => $row['price'],
            'amount' => $row['item_amount']
        ];
    }
    
    $walkin_html = '';
    $online_html = '';
    $single_html = ''; 

    foreach($grouped_orders as $order_id => $data) {
        $row = $data['main'];
        $products = $data['products'];
        $proof_file = $data['proof_file']; 
        $total_amount = array_sum(array_column($products, 'amount'));
        
        $product_display_arr = [];
        foreach ($products as $p) { $product_display_arr[] = htmlspecialchars($p['name']) . " (" . $p['qty'] . ")"; }
        $product_display = implode('<br>', $product_display_arr);

        $current_status = $row['status'];
        $customer_full_name = trim($row['first_name'] . ' ' . $row['last_name']);
        
        $is_walk_in = empty(trim($row['first_name']));
        
        $customer_display_name = $is_walk_in ? '<span style="color: #5c4273; font-weight: bold;">WALK-IN</span>' : ucwords($customer_full_name);
        $amount_display = '₱' . number_format($total_amount, 2); 
        $status_text = $status_map[$current_status];
        $is_final_status = in_array($current_status, [3, 4, 5]);  
        
        $order_type_tag = $is_walk_in ? 'walkin' : 'online';

        $filter_status = in_array($current_status, [3, 5]) ? '3,5' : $current_status;
        if($is_walk_in && !$is_final_status) { $filter_status = 4; }
        $search_data = strtolower($order_id . ' ' . $customer_full_name . ' ' . $row['email'] . ' ' . $row['mobile'] . ' ' . implode(' ', array_column($products, 'name')));
     
        $unique_row_id = "details-{$order_id}-" . uniqid();  
        
        $row_content = ''; 

        $row_content .= '<tr class="order-main-row" data-order-type="' . $order_type_tag . '" data-status="' . $filter_status . '" data-search="' . $search_data . '" data-order-id="' . $order_id . '" data-target-row="' . $unique_row_id . '">';
        $row_content .= '<td class="text-center">' . $order_id . '</td>';
        $row_content .= '<td>' . date("Y-m-d", strtotime($row['date_created'])) . '</td>';
        $row_content .= '<td class="toggle-details" style="cursor: pointer; font-weight: bold; color: #007bff; text-decoration: underline;">' . $customer_display_name . '</td>';
        $row_content .= '<td>' . $product_display . '</td>';
        $row_content .= '<td class="text-right">' . $amount_display . '</td>';
        $row_content .= '<td>' . $row['address'] . '</td>';
        
        $row_content .= '<td>';
        
        
        if ($is_walk_in) {
            $row_content .= '<span class="order-status-display walkin-badge" style="background-color: #5c4273;">WALK-IN (PAID)</span>';
        } else {
            
            if (!empty($proof_file) && $proof_file !== 'NULL') {
                $row_content .= '<button type="button" class="single-action-btn action-proof view_order_proof_btn" ';
                $row_content .= 'data-id="' . $order_id . '" title="View Proof of Payment">View Proof</button>';
            }
            if (!empty($proof_file) && $proof_file !== 'NULL' && !$is_final_status) { $row_content .= '<hr style="margin: 5px 0; border-top: 1px solid #ccc;"/>'; }

            if ($is_final_status) {
                $row_content .= '<span class="order-status-display status-' . $current_status . '">' . $status_text . '</span>';
            } else {  
                $next_status = 0;
                $action_text = '';
                $action_class = '';
                $confirmation_msg = '';
                
                if ($current_status == 0) { $next_status = 1; $action_text = 'Set to Cooking (Payment Confirmed)'; $action_class = 'action-to-1'; } 
                elseif ($current_status == 1) { $next_status = 2; $action_text = 'Set to Ready for Pickup'; $action_class = 'action-to-2'; } 
                elseif ($current_status == 2) { $next_status = 4; $action_text = 'Mark as Received'; $action_class = 'action-to-4'; $confirmation_msg = "confirm('Are you sure the customer received this order? Sales will be recorded.')"; }
                
                if ($current_status != 0) { $row_content .= '<small class="current-status-text">CURRENT: ' . $status_map[$current_status] . '</small>'; }
                
                if ($next_status > 0) {  
                    $row_content .= '<button type="button" class="single-action-btn ' . $action_class . ' update-status-btn" data-order-id="' . $order_id . '" data-new-status="' . $next_status . '" data-confirm="' . $confirmation_msg . '" title="' . $action_text . '">' . $action_text . '</button>';
                    $row_content .= '<button type="button" class="single-action-btn cancel-action-btn update-status-btn" data-order-id="' . $order_id . '" data-new-status="5" data-confirm="confirm(\'Are you sure you want to CANCEL this order?\')" title="Cancel Order">Cancel</button>';
                }
            }
        }
        $row_content .= '</td>';
        $row_content .= '</tr>';
        
        $row_content .= '<tr class="customer-details-row" id="' . $unique_row_id . '" style="display: none;">';
        $row_content .= '<td colspan="7" style="padding: 0 !important; border: none !important;">'; 
        $row_content .= '<div style="padding: 5px 20px; background: #f9f9f9; border-left: 5px solid #007bff; font-size: 13px;">';
        $row_content .= '<strong>Contact Details:</strong> &nbsp; &nbsp;';
        $row_content .= '📧 Email: <span style="font-weight: 500; margin-right: 15px;">' . htmlspecialchars($row['email']) . '</span> &nbsp; | &nbsp; &nbsp; ';
        $row_content .= '📱 Mobile: <span style="font-weight: 500;">' . htmlspecialchars($row['mobile']) . '</span>';
        $row_content .= '</div>';
        $row_content .= '</td>';
        $row_content .= '</tr>';

        $single_html .= $row_content; 

        if (!$specific_order_id) {
            if ($is_walk_in) {
                $walkin_html .= $row_content;
            } else {
                $online_html .= $row_content;
            }
        }
    }

    if (!$specific_order_id) {
        if (empty($online_html)) {
            $online_html = '<tr class="no-results"><td colspan="7" class="text-center">No online orders found.</td></tr>'; 
        }
        if (empty($walkin_html)) {
            $walkin_html = '<tr class="no-results"><td colspan="7" class="text-center">No walk-in orders found.</td></tr>'; 
        }
    }
    
    return ['online_html' => $online_html, 'walkin_html' => $walkin_html, 'single_html' => $single_html];
}

$table_data = generate_order_rows($conn);  
$online_rows_html = $table_data['online_html'];
$walkin_rows_html = $table_data['walkin_html'];
?>

<div class="container-fluid">
    <div id="admin-order-alert-container"></div>  
    
    <div class="card mb-4" style="border-radius: 10px; border-top: 5px solid #007bff;">
        <div class="card-header bg-primary text-white" style="background-color: #007bff !important;">
            <h4 class="m-0">🌐 Online Orders (Requires Action)</h4>
        </div>
        <div class="card-body">
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="order-status-filter">Filter by Status:</label>
                    <select id="order-status-filter" class="form-control form-control-sm">
                        <option value="">-- Show All Statuses --</option>
                        <option value="0">Queued (Online)</option>
                        <option value="1">Cooking</option>
                        <option value="2">Ready for Pickup</option>
                        <option value="4">Received</option>
                        <option value="3,5">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="online-order-search">Search Customer/Product:</label>
                    <input type="text" id="online-order-search" class="form-control form-control-sm" placeholder="Enter Order ID, Name, Product, Email, or Mobile">
                </div>
            </div>
            
            <table class="table table-bordered" id="online-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>  
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>Product (Qty)</th>
                        <th class="text-right">Amount</th>  
                        <th>Address</th>
                        <th>Status / Action</th>  
                    </tr>
                </thead>
                <tbody id="online-order-table-body">
                    <?php echo $online_rows_html; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <hr>
    
    <div class="card" style="border-radius: 10px; border-top: 5px solid #5c4273;">
        <div class="card-header text-white" style="background-color: #5c4273 !important;">
            <h4 class="m-0">🚶 Walk-in Orders (Paid)</h4>
        </div>
        <div class="card-body">
            <p class="text-muted small"></p>
            
            <table class="table table-bordered" id="walkin-orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>  
                        <th>Date</th>
                        <th>Customer Name</th>
                        <th>Product (Qty)</th>
                        <th class="text-right">Amount</th>  
                        <th>Address</th>
                        <th>Status / Action</th>  
                    </tr>
                </thead>
                <tbody id="walkin-order-table-body">
                    <?php echo $walkin_rows_html; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.card { border-radius: 10px !important; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.table-bordered {  
    border-radius: 10px !important;  
    overflow: hidden;  
    border-collapse: separate;
    border-spacing: 0;  
    margin-bottom: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);  
}
.table-bordered th {
    background-color: #f8f9fa;  
    font-weight: 600;
    color: #343a40;
}
.table-bordered tr:hover:not(.customer-details-row) {
    background-color: #f2f7ff;  
}
.single-action-btn {  
    padding: 6px 10px;  
    font-size: 11px;  
    font-weight: bold;
    width: 100%;  
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    margin-bottom: 3px;  
}
.order-status-display {
    padding: 6px 10px;
    font-size: 11px;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
    display: block;  
}

.status-0 { background-color: #ffc107; color: #343a40; }  
.status-1 { background-color: #17a2b8; color: white; }  
.status-2 { background-color: #28a745; color: white; }  
.status-4 { background-color: #007bff; color: white; }  
.status-3, .status-5 { background-color: #dc3545; color: white; }  

.action-to-1 { background-color: #17a2b8; }  
.action-to-2 { background-color: #28a745; }  
.action-to-4 { background-color: #007bff; }  
.cancel-action-btn { background-color: #dc3545; }  
.action-proof { background-color: #6c757d; }

.walkin-badge {
    background-color: #5c4273 !important; 
    color: white;
}

.current-status-text {
    font-size: 10px;
    color: #6c757d;
    text-transform: uppercase;
    display: block;
    text-align: center;
    margin-bottom: 5px;
}
.table-bordered .text-right {
    font-weight: 600;
    color: #007bff;  
}
.customer-details-row td {
    padding: 0 !important;
    border: none !important;
}
</style>

<script>
    function updateOrderStatus(orderId, newStatus, confirmMsg) {
        if (confirmMsg && !eval(confirmMsg)) { return; }

        var $oldRow = $('#online-orders-table tr[data-order-id="' + orderId + '"]');
        var $btns = $oldRow.find('.update-status-btn, .view_order_proof_btn'); 
        
        $btns.prop('disabled', true);
        $oldRow.css('opacity', 0.5); 
        $btns.text('...Updating...');
        
        $.ajax({
            url: 'ajax.php?action=update_order_status',  
            method: 'POST',
            data: { order_id: orderId, new_status: newStatus },
            dataType: 'json',  
            success: function(response) {
                if (response.status === 'success') {
                    $.ajax({
                        url: 'ajax.php?action=get_single_order_html',
                        method: 'POST',
                        data: { order_id: orderId },
                        dataType: 'json',
                        success: function(singleRowResponse) {
                            if (singleRowResponse.status === 'success' && singleRowResponse.html) {
                                var $newRowHtml = $(singleRowResponse.html);
                                
                                $oldRow.next('.customer-details-row[id^="details-' + orderId + '"]').remove();
                                $oldRow.remove(); 
                                
                                $('#online-order-table-body').prepend($newRowHtml);
                                
                                applyFilters();
                                attachAllHandlers(); 
                                console.log("Order status updated instantly.");
                            } else {
                                console.error("Failed to fetch single row HTML. Resorting to full refresh.");
                                checkOrdersAndRefresh(); 
                            }
                        },
                        error: function() {
                            console.error("AJAX Error fetching single row. Resorting to full refresh.");
                            checkOrdersAndRefresh(); 
                        }
                    });
                } else {
                    alert("⚠️ Error updating status. Error Message: " + (response.msg || "Unknown error."));  
                    $oldRow.css('opacity', 1.0);
                    $btns.prop('disabled', false).filter('.update-status-btn').text('Retry');
                    
                    checkOrdersAndRefresh();  
                }
            },
            error: function(xhr, status, error) {
                alert("❌ AJAX Error: An internal server error occurred during update.");
                $oldRow.css('opacity', 1.0);
                $btns.prop('disabled', false).filter('.update-status-btn').text('Retry');
            }
        });
    }

    function refreshOrderTableAndApplyFilter(onlineHtml, walkinHtml) {
        var currentStatusFilter = $('#order-status-filter').val();
        var currentSearchTerm = $('#online-order-search').val();
        
        $('#online-order-table-body').html(onlineHtml);
        $('#walkin-order-table-body').html(walkinHtml);
        
        $('#order-status-filter').val(currentStatusFilter);
        $('#online-order-search').val(currentSearchTerm);
        
        applyFilters();  
        attachAllHandlers();
    }
    
    function checkOrdersAndRefresh() {
        $.ajax({
            url: 'ajax.php?action=get_orders_split_html',  
            method: 'POST',
            dataType: 'json',  
            success: function(response) {
                if (response.status === 'success' && response.online_html !== undefined && response.walkin_html !== undefined) {
                    refreshOrderTableAndApplyFilter(response.online_html, response.walkin_html);  
                }
            },
            error: function(xhr, status, error) {
            }
        });
    }

    function applyFilters() {
        var statusFilter = $('#order-status-filter').val();
        var searchTerm = $('#online-order-search').val().toLowerCase().trim();
        var visibleCount = 0;
        var hasNoResultsRow = $('#online-orders-table tbody').find('tr.no-results').length > 0;

        $('#online-orders-table tbody tr.order-main-row').each(function() {
            var row = $(this);
            var rowStatus = row.data('status').toString();  
            var rowSearch = row.data('search');
            var detailsRowId = row.data('target-row');

            var statusMatch = (statusFilter === '' || statusFilter.split(',').includes(rowStatus));
            var searchMatch = (searchTerm === '' || rowSearch.includes(searchTerm));

            if (statusMatch && searchMatch) {
                row.show();
                $('#' + detailsRowId).hide();
                visibleCount++;
            } else {
                row.hide();
                $('#' + detailsRowId).hide();
            }
        });
        
        if (hasNoResultsRow) {
            var $noResultsRow = $('#online-orders-table tbody').find('tr.no-results');
            if (visibleCount === 0) {
                $noResultsRow.show();
            } else {
                $noResultsRow.hide();
            }
        }
    }
    
    function attachToggleDetailsHandler() {
        $('.toggle-details').off('click').on('click', function() {
            var $mainRow = $(this).closest('tr');
            var targetRowId = $mainRow.data('target-row');
            
            $('.customer-details-row').not('#' + targetRowId).slideUp(200);
            $('#' + targetRowId).slideToggle(200);
        });
    }
    
    function attachAllHandlers() {
        $('#online-orders-table .update-status-btn').off('click').on('click', function() {
            var orderId = $(this).data('order-id');
            var newStatus = $(this).data('new-status');
            var confirmMsg = $(this).data('confirm');
            
            updateOrderStatus(orderId, newStatus, confirmMsg);
        });
        
        attachToggleDetailsHandler();
        $('.view_order_proof_btn').off('click').on('click', function(e) {
            e.preventDefault();  
            var order_id = $(this).attr('data-id');
            
            window.parent.uni_modal(
                "Order Details (ID: " + order_id + ")",  
                "view_order.php?id=" + order_id,  
                "large"
            );
        });
    }

    $(document).ready(function() {
     
        applyFilters();  
        attachAllHandlers();

        $('#order-status-filter').on('change', function() {
            applyFilters();
        });
        $('#online-order-search').on('keyup', function() {
            applyFilters();
        });
        
        setInterval(checkOrdersAndRefresh, 7000);  
    });
</script>