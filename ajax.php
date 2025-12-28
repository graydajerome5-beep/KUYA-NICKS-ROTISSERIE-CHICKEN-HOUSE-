<?php
ob_start();

include 'db_connect.php';
include 'admin_class.php';

$status_map = [
    0 => 'Queued',
    1 => 'Cooking',
    2 => 'Ready for Pickup',
    3 => 'Canceled',
    4 => 'Received',
    5 => 'Canceled'
];

function generate_order_rows($conn) {
    global $status_map; 

    $orders_query = $conn->query("
        SELECT  
            o.id AS order_id,
            o.status,
            o.date_created,
            u.first_name,  
            u.last_name,
            u.address,
            u.email,
            u.mobile,
            p.name AS product_name,
            ol.qty,
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
        ORDER BY  
            FIELD(o.status, 0, 1, 2, 4, 3, 5), o.date_created DESC
    ");
    
    $html = '';

    if ($orders_query === false) {
        return '<tr><td colspan="7" class="text-center no-results">Error executing query: ' . $conn->error . '</td></tr>';
    }

    if ($orders_query->num_rows > 0) {
        while($row = $orders_query->fetch_assoc()) {
            $order_id = $row['order_id']; 
            $current_status = $row['status'];
            $customer_full_name = ucwords($row['first_name'] . ' ' . $row['last_name']);
            $product_display = htmlspecialchars($row['product_name']) . " (" . $row['qty'] . ")";
            $amount_display = '₱' . number_format($row['item_amount'], 2);
            
            $status_text = $status_map[$current_status];
            $is_final_status = in_array($current_status, [3, 4, 5]); 
            
            $filter_status = in_array($current_status, [3, 5]) ? '3,5' : $current_status;
            $search_data = strtolower($order_id . ' ' . $customer_full_name . ' ' . $row['email'] . ' ' . $row['mobile'] . ' ' . $row['product_name']);

            $unique_row_id = "details-{$order_id}-" . uniqid(); 

            $html .= '<tr class="order-main-row" data-status="' . $filter_status . '" data-search="' . $search_data . '" data-order-id="' . $order_id . '" data-target-row="' . $unique_row_id . '">';
            $html .= '<td class="text-center">' . $order_id . '</td>';
            $html .= '<td>' . date("Y-m-d", strtotime($row['date_created'])) . '</td>';
            
            $html .= '<td class="toggle-details" style="cursor: pointer; font-weight: bold; color: #007bff; text-decoration: underline;">';
            $html .= $customer_full_name;
            $html .= '</td>';
            
            $html .= '<td>' . $product_display . '</td>';
            $html .= '<td class="text-right">' . $amount_display . '</td>';
            $html .= '<td>' . $row['address'] . '</td>';
            
            $html .= '<td>';
            if ($is_final_status) {
                $html .= '<span class="order-status-display status-' . $current_status . '">' . $status_text . '</span>';
            } else {  
                $next_status = 0;
                $action_text = '';
                $action_class = '';
                $confirmation_msg = '';
                
                if ($current_status == 0) {
                    $next_status = 1;
                    $action_text = 'Set to Cooking';
                    $action_class = 'action-to-1';
                } elseif ($current_status == 1) {
                    $next_status = 2;
                    $action_text = 'Set to Ready for Pickup';
                    $action_class = 'action-to-2';
                } elseif ($current_status == 2) {
                    $next_status = 4; 
                    $action_text = 'Mark as Received';
                    $action_class = 'action-to-4';
                    $confirmation_msg = "confirm('Are you sure the customer received this order? Sales will be recorded.')";
                }
                
                if ($next_status > 0) {  
                    $html .= '<small class="current-status-text">Current: ' . $status_text . '</small>';
                    $html .= '<button type="button" class="single-action-btn ' . $action_class . ' update-status-btn" ';
                    $html .= 'data-order-id="' . $order_id . '" data-new-status="' . $next_status . '" data-confirm="' . $confirmation_msg . '" title="' . $action_text . '">' . $action_text . '</button>';
                    
                    $html .= '<button type="button" class="single-action-btn cancel-action-btn update-status-btn" ';
                    $html .= 'data-order-id="' . $order_id . '" data-new-status="5" data-confirm="confirm(\'Are you sure you want to CANCEL this order?\')" title="Cancel Order">Cancel</button>';
                }
            }
            $html .= '</td>';
            $html .= '</tr>';
            
            $html .= '<tr class="customer-details-row" id="' . $unique_row_id . '" style="display: none;">';
            $html .= '<td colspan="7" style="padding: 0 !important; border: none !important;">';
            $html .= '<div style="padding: 5px 20px; background: #f9f9f9; border-left: 5px solid #007bff; font-size: 13px;">';
            $html .= '<strong>Contact Details:</strong> &nbsp; &nbsp;';
            $html .= '📧 Email: <span style="font-weight: 500; margin-right: 15px;">' . htmlspecialchars($row['email']) . '</span> &nbsp; | &nbsp; &nbsp; ';
            $html .= '📱 Mobile: <span style="font-weight: 500;">' . htmlspecialchars($row['mobile']) . '</span>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';

        }
    } else {
        $html .= '<tr><td colspan="7" class="text-center no-results">No orders found.</td></tr>';
    }
    
    return $html;
}
function makeValuesReferenced($arr){
    $refs = array();
    foreach($arr as $key => $value)
        $refs[$key] = &$arr[$key];
    return $refs;
}

$action = $_GET['action'];
$crud = new Action();

if($action == 'login'){
	$login = $crud->login();
	if($login)
		echo $login;
}
if($action == 'login2'){
	$login = $crud->login2();
	if($login)
		echo $login;
}
if($action == 'logout'){
	$logout = $crud->logout();
	if($logout)
		echo $logout;
}
if($action == 'logout2'){
	$logout = $crud->logout2();
	if($logout)
		echo $logout;
}
if($action == 'save_user'){
	$save = $crud->save_user();
	if($save)
		echo $save;
}
if($action == 'signup'){
	$save = $crud->signup();
	if($save)
		echo $save;
}
if($action == "save_settings"){
	$save = $crud->save_settings();
	if($save)
		echo $save;
}
if($action == "save_category"){
	$save = $crud->save_category();
	if($save)
		echo $save;
}
if($action == "delete_category"){
	$save = $crud->delete_category();
	if($save)
		echo $save;
}
if($action == "save_menu"){
	$save = $crud->save_menu();
	if($save)
		echo $save;
}
if($action == "delete_menu"){
	$save = $crud->delete_menu();
	if($save)
		echo $save;
}
if($action == "add_to_cart"){
	$save = $crud->add_to_cart();
	if($save)
		echo $save;
}
if($action == "get_cart_count"){
	$save = $crud->get_cart_count();
	if($save)
		echo $save;
}
if($action == "delete_cart"){
	$delete = $crud->delete_cart();
	if($delete)
		echo $delete;
}
if($action == "update_cart_qty"){
	$save = $crud->update_cart_qty();
	if($save)
		echo $save;
}
if($action == "save_order"){
	$save = $crud->save_order();
	if($save){
		echo $save;
	} else {
		echo "0";
	}
}

if($action == "update_order_status"){
	$save = $crud->update_order_status();
	
	header('Content-Type: application/json');
	if($save)
		echo $save;

}

if($action == "submit_feedback"){
	$result = $crud->submit_feedback();
	header('Content-Type: application/json');
	echo $result;
}
if($action == "cancel_order"){
	$cancel = $crud->cancel_order();
	if($cancel)
		echo $cancel;
}

if($action == "check_new_orders"){ 
    $response = [
        'has_new' => false, 
        'has_updates' => false, 
        'count' => 0,
        'server_time' => date('Y-m-d H:i:s')
    ];

    $last_checked = isset($_POST['last_checked']) ? $_POST['last_checked'] : date('Y-m-d H:i:s'); 

    global $conn; 

    $check_query = $conn->query("
        SELECT 
            SUM(CASE WHEN status = 0 AND date_created > '$last_checked' THEN 1 ELSE 0 END) AS new_queued_count,
            COUNT(id) AS total_updates_count
        FROM 
            orders 
        WHERE 
            date_created > '$last_checked' OR date_updated > '$last_checked'
    ");
    
    if ($check_query) { 
        $result = $check_query->fetch_assoc();
        $new_queued_count = (int)$result['new_queued_count'];
        $total_updates_count = (int)$result['total_updates_count'];

        if ($new_queued_count > 0) {
            $response['has_new'] = true;
            $response['count'] = $new_queued_count;
        }
        if ($total_updates_count > 0) {
            $response['has_updates'] = true;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
if($action == "get_orders_html"){
    global $conn;

    $html_content = generate_order_rows($conn);

    $response = array(
        'status' => 'success',
        'html' => $html_content
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($action == 'check_customer_order_updates') {
    $orders_to_check = isset($_POST['orders']) ? $_POST['orders'] : []; 
    $order_ids = array_keys($orders_to_check);

    $response = ['status' => 'success', 'has_updates' => false, 'updates' => []];

    if (!empty($order_ids)) {
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $types = str_repeat('i', count($order_ids));
        
        $order_ids_int = array_map('intval', $order_ids);

        global $conn;

        $stmt = $conn->prepare("SELECT id, status FROM orders WHERE id IN ($placeholders)");
        
        $params = array_merge([$types], $order_ids_int);
        call_user_func_array(array($stmt, 'bind_param'), makeValuesReferenced($params));
        
        $stmt->execute();
        $result = $stmt->get_result();

        function get_customer_status_badge_server($status_code) {
            switch ($status_code) {
                case 0: return ['text' => 'Queued', 'class' => 'badge-warning', 'is_final' => false];
                case 1: return ['text' => 'Cooking', 'class' => 'badge-info', 'is_final' => false];
                case 2: return ['text' => 'Ready for Pickup', 'class' => 'badge-success', 'is_final' => false];
                case 3: 
                case 5: return ['text' => 'Canceled', 'class' => 'badge-danger', 'is_final' => true];
                case 4: return ['text' => 'Order Received', 'class' => 'badge-primary', 'is_final' => true];
                default: return ['text' => 'Unknown', 'class' => 'badge-secondary', 'is_final' => true];
            }
        }

        while ($row = $result->fetch_assoc()) {
            $order_id = $row['id'];
            $db_status_info = get_customer_status_badge_server($row['status']);
            $db_status_text = $db_status_info['text'];
            if (isset($orders_to_check[$order_id]) && $orders_to_check[$order_id] !== $db_status_text) {
                $response['has_updates'] = true;
                $response['updates'][$order_id] = $db_status_info;
            }
        }

        $stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    $conn->close();
    exit;
}

ob_end_flush();
exit; 

?>