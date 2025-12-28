<?php

include 'db_connect.php'; 

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$view_history = false; 


if (!$order_id) {
    echo "<div class='alert alert-danger'>Error: Order ID not received.</div>";
    exit();
}

$title_text = "Order Details";
$sql_condition = "o.id = $order_id";
$customer_full_name = "";


$orders_query = $conn->query("
    SELECT 
        o.id AS order_id, 
        o.status, 
        o.date_created,
        o.proof_file, /* <--- KUNIN ANG PROOF FILE PATH */
        u.first_name, 
        u.last_name, 
        u.address, 
        u.email, 
        u.mobile,
        u.user_id 
    FROM orders o
    JOIN user_info u ON o.user_id = u.user_id
    WHERE $sql_condition
    ORDER BY o.date_created DESC
");

if (!$orders_query || $orders_query->num_rows === 0) {
    echo "<div class='alert alert-info'>No order found with ID: {$order_id}</div>";
    exit();
}

$order_details = [];
while($row = $orders_query->fetch_assoc()) {
    $order_details[] = $row;
}


$main_order_info = $order_details[0];
    
$customer_id = $main_order_info['user_id'];
    
$customer_full_name = ucwords($main_order_info['first_name'] . ' ' . $main_order_info['last_name']);
$main_customer_info = [
    'first_name' => $main_order_info['first_name'],
    'last_name' => $main_order_info['last_name'],
    'address' => $main_order_info['address'],
    'email' => $main_order_info['email'],
    'mobile' => $main_order_info['mobile']
]; 


function get_status_badge($status) {
    $status_text = '';
    $badge_class = 'badge-secondary';
    if ($status == 0) {
        $status_text = 'Queued';
        $badge_class = 'badge-warning';
    } elseif ($status == 1) {
        $status_text = 'Cooking';
        $badge_class = 'badge-info';
    } elseif ($status == 2) {
        $status_text = 'Ready for Pickup';
        $badge_class = 'badge-success';
    } elseif ($status == 3 || $status == 5) {
        $status_text = 'Canceled';
        $badge_class = 'badge-danger';
    } elseif ($status == 4) {
        $status_text = 'Order Received';
        $badge_class = 'badge-primary';
    }
    return "<span class='badge {$badge_class}'>{$status_text}</span>";
}


function get_order_items($conn, $id) {
    $items_query = $conn->query("
        SELECT ol.qty, p.name AS product_name, p.price, (ol.qty * p.price) AS total
        FROM order_list ol
        JOIN product_list p ON ol.product_id = p.id
        WHERE ol.order_id = $id
    ");
    $items = [];
    $grand_total = 0;
    if ($items_query) {
        while($row = $items_query->fetch_assoc()) {
            $items[] = $row;
            $grand_total += $row['total'];
        }
    }
    return ['items' => $items, 'grand_total' => $grand_total];
}
?>

<div class="container-fluid">
    <?php if(isset($main_customer_info)): ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <p><strong>Name:</strong> <?php echo $customer_full_name; ?></p>
            <p><strong>Mobile:</strong> <?php echo $main_customer_info['mobile']; ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Address:</strong> <?php echo $main_customer_info['address']; ?></p>
            <p><strong>Email:</strong> <?php echo $main_customer_info['email']; ?></p>
        </div>
    </div>
    <hr>
    <?php endif; ?>
    
    <?php if (isset($main_order_info)):  ?>
    <div class="row">
        <div class="col-md-12">
            <h5>Order ID Details: <?php echo $order_id; ?></h5>
            <p><strong>Date Ordered:</strong> <?php echo date("M d, Y h:i A", strtotime($main_order_info['date_created'])); ?></p>
            <p><strong>Current Status:</strong> <?php echo get_status_badge($main_order_info['status']); ?></p>
            
            <?php 
                $proof_path = $main_order_info['proof_file'] ?? '';
                if (!empty($proof_path) && $proof_path !== 'NULL'): 
            ?>
                <div class="card card-outline card-success mt-4">
                    <div class="card-header">
                        <h5 class="card-title">📸 Proof of Payment</h5>
                    </div>
                    <div class="card-body text-center">
                        <img 
                            src="../<?php echo htmlspecialchars($proof_path); ?>" 
                            alt="Customer Proof of Payment" 
                            style="max-width: 100%; height: auto; border: 3px solid #28a745; border-radius: 5px; cursor: pointer;"
                            onclick="window.open(this.src);" 
                        >
                        <small class="text-muted d-block mt-2">Click the image to view the original file.</small>
                    </div>
                </div>
            <?php else: ?>
                <p class="alert alert-warning mt-4"><strong>Note:</strong> No Proof of Payment was uploaded for this order.</p>
            <?php endif; ?>
            <?php
            $order_data = get_order_items($conn, $order_id);
            $items = $order_data['items'];
            $grand_total = $order_data['grand_total'];
            ?>
            <h5 class="mt-3">Products in Order</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan='4' class='text-center'>No items found for this order.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['product_name']; ?></td>
                            <td><?php echo $item['qty']; ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>₱<?php echo number_format($item['total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Grand Total:</th>
                        <th>₱<?php echo number_format($grand_total, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    </div>

<script>
    $(document).ready(function(){
        
        var $modalTitle = window.parent.$('#uni_modal .modal-title');
        var $modalHeader = window.parent.$('#uni_modal .modal-header');
        
        function modal_close_function() {
            if (window.parent.modal_close) {
                window.parent.modal_close();
            } else {
                window.parent.$('#uni_modal').modal('hide');
            }
        }
        window.parent.$('.back-btn-injected').remove();
        window.parent.$('.close-btn-injected').remove();
        
        <?php if ($order_id): ?>
            
            var newTitle = "<?php echo $title_text; ?> (ID: <?php echo $order_id; ?>)";
            
            if ($modalTitle.length) {
                $modalTitle.text(newTitle);
            }

            if ($modalHeader.length) {
                $modalHeader.find('button.close').remove(); 

                $modalHeader.append('<button class="btn btn-sm btn-danger float-right ml-2 close-btn-injected" type="button" title="Close"><i class="fa fa-times"></i></button>');
                
                window.parent.$('.close-btn-injected').off('click').click(function(){
                    modal_close_function();
                });
            }
        <?php endif; ?>
    });
</script>