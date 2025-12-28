<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'admin/db_connect.php';

$user_id = isset($_SESSION['login_user_id']) ? $_SESSION['login_user_id'] : 0;

$orders_data = [];
if ($user_id > 0 && $conn) {
    
    $qry = $conn->query("SELECT o.* FROM orders o WHERE o.user_id = '$user_id' ORDER BY o.date_updated DESC");
    if ($qry) {
        while ($row = $qry->fetch_assoc()) {
            $order_id = $row['id'];
            $items_qry = $conn->query("SELECT ol.*, p.name, p.price FROM order_list ol INNER JOIN product_list p ON ol.product_id = p.id WHERE ol.order_id = '$order_id'");
            $items = [];
            $total_amount = 0;
            while ($item = $items_qry->fetch_assoc()) {
                $items[] = $item;
                $total_amount += $item['price'] * $item['qty'];
            }
            $row['items'] = $items;
            $row['total_amount'] = $total_amount;
            $orders_data[] = $row;
        }
    }
}
?>

<style>

:root {
    --table-border-color: #e0e0e0; 
}
.table-responsive-wrapper {
    overflow-x: auto; 
    width: 100%; 
    -webkit-overflow-scrolling: touch; 
}

.custom-bordered-table {
    min-width: 650px; 
}

.custom-white-card {
    border-radius: 12px !important; 
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05) !important;
    overflow: hidden; 
    border: 5px solid var(--table-border-color) !important; 
}

.custom-bordered-table thead th {
    background-color: #f8f9fa; 
    color: #333; 
    font-weight: bold;
    padding: 10px;
    border: 1px solid var(--table-border-color) !important; 
    border-bottom-width: 2px !important; 
}

.custom-bordered-table tbody td {
    border: 1px solid var(--table-border-color) !important; 
    padding: 8px;
}

.order-main-row {
    background-color: #ffffff !important;
}

.order-item-detail-row {
    background-color: #fcfcfc; 
    font-size: 0.9em;
    color: #555;
}

.order-item-detail-row td {
    padding: 4px 8px 4px 40px; 
    border-top: none !important;
    
    border-bottom: 1px solid var(--table-border-color) !important; 
}

.order-main-row .total-amount-cell {
    font-weight: bold;
    color: #007bff; 
}

.table .badge {
    border-radius: 50px;
    padding: 5px 10px; 
    font-size: 0.8em;
    font-weight: 600;
}
</style>

<header class="masthead">
    <div class="container h-100">
        <div class="row h-100 align-items-center justify-content-center text-center">
            <div class="col-lg-10 align-self-center mb-4 page-title">
            
            </div>
        </div>
    </div>
</header>
<section class="page-section" id="menu">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card custom-white-card"> 
                    <div class="card-body">
                        <div class="table-responsive-wrapper"> 
                            <table class="table table-bordered custom-bordered-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Order ID</th>
                                        <th>Order Date</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders_data)): ?>
                                    <?php $i = 1; ?>
                                    <?php foreach ($orders_data as $order): ?>
                                    <tr class="order-main-row" id="order-row-<?php echo $order['id']; ?>" data-order-id="<?php echo $order['id']; ?>">
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($order['date_updated'])); ?></td>
                                        <td class="total-amount-cell">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td class="status-cell">
                                            <span id="status-badge-<?php echo $order['id']; ?>">
                                            <?php
                                            $status_text = '';
                                            $badge_class = '';
                                            if ($order['status'] == 0) {
                                                $status_text = 'Queued';
                                                $badge_class = 'badge-warning';
                                            } elseif ($order['status'] == 1) {
                                                $status_text = 'Cooking';
                                                $badge_class = 'badge-info';
                                            } elseif ($order['status'] == 2) {
                                                $status_text = 'Ready for Pickup';
                                                $badge_class = 'badge-success';
                                            } elseif ($order['status'] == 4) { 
                                                $status_text = 'Order Received';
                                                $badge_class = 'badge-primary'; 
                                            } elseif ($order['status'] == 3 || $order['status'] == 5) { 
                                                $status_text = 'Canceled';
                                                $badge_class = 'badge-danger'; 
                                            } else {
                                                $status_text = 'Pending';
                                                $badge_class = 'badge-secondary';
                                            }
                                            echo '<span class="badge ' . $badge_class . '">' . $status_text . '</span>';
                                            ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $order_date_updated = new DateTime($order['date_updated']);
                                            $now = new DateTime();
                                            $interval = $now->diff($order_date_updated);
                                            $seconds_elapsed = $interval->s + ($interval->i * 60) + ($interval->h * 3600) + ($interval->days * 86400);
                                            $total_cancellation_time = 2 * 60;
                                            $can_cancel = ($order['status'] == 0 && $seconds_elapsed < $total_cancellation_time);
                                            $remaining_seconds = max(0, $total_cancellation_time - $seconds_elapsed);
                                            ?>
                                            
                                            <?php if ($order['status'] == 0): ?>
                                                <div class="cancel-timer-container">
                                                    <button class="btn btn-sm btn-danger cancel-order-btn" data-id="<?php echo $order['id']; ?>" <?php echo $can_cancel ? '' : 'disabled'; ?>>
                                                        <i class="fa fa-times"></i> Cancel Order
                                                    </button>
                                                    <?php if ($can_cancel): ?>
                                                        <span class="cancel-timer text-danger" data-seconds-remaining="<?php echo $remaining_seconds; ?>"></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                        </td>
                                    </tr>
                                    <?php foreach ($order['items'] as $item): ?>
                                    <tr class="order-item-detail-row"> 
                                        <td colspan="2"></td>
                                        <td>Product: <?php echo $item['name']; ?></td>
                                        <td>Quantity: <?php echo $item['qty']; ?></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    var currentOrderStatuses = {}; 
    function startTimer(display) {
        let timer = parseInt(display.data('seconds-remaining'), 10);
        const button = display.siblings('.cancel-order-btn');

        if (timer > 0) {
            button.prop('disabled', false).removeClass('btn-secondary').addClass('btn-danger');
            let interval = setInterval(function () {
                let minutes = parseInt(timer / 60, 10);
                let seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.text(" (" + minutes + ":" + seconds + ")");

                if (--timer < 0) {
                    clearInterval(interval);
                    button.prop('disabled', true).addClass('btn-secondary').removeClass('btn-danger');
                    display.text(' (Expired)');
                }
            }, 1000);
            button.data('timer-interval', interval);
        } else {
            button.prop('disabled', true).addClass('btn-secondary').removeClass('btn-danger');
            display.text(' (Expired)');
        }
    }
    function initializeOrderStatusesFromPage() {
        $('.order-main-row').each(function() {
            var orderId = $(this).data('order-id');
            var statusText = $(this).find('#status-badge-' + orderId + ' .badge').text().trim();
            if (orderId && statusText) {
                currentOrderStatuses[orderId] = statusText;
            }
        });
    }
    function checkOrderStatusUpdates() {
        var orders_to_check = currentOrderStatuses; 

        if (Object.keys(orders_to_check).length === 0) {
            return;
        }
        $.ajax({
            url: 'admin/ajax.php?action=check_customer_order_updates', 
            method: 'POST',
            data: { orders: orders_to_check }, 
            dataType: 'json',
            success: function(response) {
                
                if (response.status === 'success' && response.has_updates) {
                    
                    var alertMessage = "Order Status Update:\n\n";
                    $.each(response.updates, function(orderId, newStatus) {
                        var oldStatus = currentOrderStatuses[orderId];
                        
                        if (oldStatus !== newStatus.text) {
                            alertMessage += `Order #${orderId} changed from ${oldStatus} to ${newStatus.text}.\n`;
                            
                            currentOrderStatuses[orderId] = newStatus.text;
                            
                            var $orderRow = $('#order-row-' + orderId);
                            var $statusBadgeContainer = $orderRow.find('#status-badge-' + orderId);
                            var $cancelButton = $orderRow.find('.cancel-order-btn');
                            var $cancelTimer = $orderRow.find('.cancel-timer');

                            var newBadgeHtml = `<span class="badge ${newStatus.class}">${newStatus.text}</span>`;
                            $statusBadgeContainer.html(newBadgeHtml);
                            
                            if (newStatus.status_id !== 0) { 
                                if ($cancelButton.length) {
                                    $cancelButton.prop('disabled', true).addClass('btn-secondary').removeClass('btn-danger');
                                    if ($cancelTimer.length) {
                                        $cancelTimer.text(' (Status Changed)');
                                    }
                                    
                                    var intervalId = $cancelButton.data('timer-interval');
                                    if (intervalId) {
                                        clearInterval(intervalId);
                                        $cancelButton.data('timer-interval', null); 
                                    }
                                }
                            }
                        }
                    });
                    if (alertMessage !== "Order Status Update:\n\n") {
                        alert(alertMessage);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error checking customer orders:", error);
            }
        });
    }

    $(document).ready(function() {
        $('.cancel-timer').each(function() {
            startTimer($(this));
        });

        initializeOrderStatusesFromPage();
        
        setInterval(checkOrderStatusUpdates, 5000); 
        
        $(document).on('click', '.cancel-order-btn', function() {
            var orderId = $(this).data('id');
            
            if (!$(this).is(':disabled')) {
                if (confirm("Are you sure you want to cancel this order?")) {  
                    $.ajax({
                        url: 'admin/ajax.php?action=cancel_order',
                        method: 'POST',
                        data: { id: orderId },
                        success: function(response) {
                            if (response == 1) {
                                alert('Order has been successfully cancelled.');  
                                location.reload();
                            } else {
                                alert('Failed to cancel order.');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');  
                        }
                    });
                }
            }
        });
    });
</script>