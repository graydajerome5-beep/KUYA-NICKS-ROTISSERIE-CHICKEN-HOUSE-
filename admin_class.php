<?php
session_start();
Class Action {
	private $db;

	public function __construct() {
		ob_start();
		include 'db_connect.php';
		
		$this->db = $conn;
		$this->create_feedback_table();
	}

	function __destruct() {
		$this->db->close();
		ob_end_flush();
	}

	private function create_feedback_table(){
		$sql = "CREATE TABLE IF NOT EXISTS `feedback` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`order_id` int(11) NOT NULL,
				`customer_id` int(11) NOT NULL,
				`rating` int(11) NOT NULL,
				`comment` text NOT NULL,
				`date_submitted` datetime NOT NULL,
				PRIMARY KEY (`id`),
				FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
				FOREIGN KEY (`customer_id`) REFERENCES `user_info`(`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
		$this->db->query($sql);

		$check_col = $this->db->query("SHOW COLUMNS FROM `orders` LIKE 'feedback_submitted'");
		if($check_col->num_rows == 0){
			$this->db->query("ALTER TABLE `orders` ADD `feedback_submitted` INT(1) NOT NULL DEFAULT '0'");
		}
	}

	function login(){
		extract($_POST);
		$stmt = $this->db->prepare("SELECT * FROM `users` WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows > 0){
			$row = $result->fetch_array();
			if(password_verify($password, $row['password'])){
				foreach ($row as $key => $value) {
					if($key != 'password' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
				return 1;
			}
		}
		return 3;
	}
	function login2(){
		extract($_POST);
		$stmt = $this->db->prepare("SELECT * FROM user_info WHERE email = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows > 0){
			$row = $result->fetch_array();
			$is_verified = password_verify($password, $row['password']);
			if($is_verified){
				foreach ($row as $key => $value) {
					if($key != 'password' && !is_numeric($key))
						$_SESSION['login_'.$key] = $value;
				}
				$ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
				$this->db->query("UPDATE cart set user_id = '".$_SESSION['login_user_id']."' where client_ip ='$ip' ");
				return 1;
			}
		}
		return 3;
	}

	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
	}
	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../index.php");
	}

	function save_user(){
		extract($_POST);
		$password = password_hash($password, PASSWORD_DEFAULT);
		$data = " `firstname` = '$firstname' ";
                $data = " `lastname` = '$lastname' ";
		$data .= ", `username` = '$username' ";
		$data .= ", `password` = '$password' ";
		$data .= ", `type` = '$type' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO users set ".$data);
		}else{
			$save = $this->db->query("UPDATE users set ".$data." where id = ".$id);
		}
		if($save){
			return 1;
		}
	}
	function signup(){
		extract($_POST);
		$password = password_hash($password, PASSWORD_DEFAULT);
		$data = " first_name = '$first_name' ";
		$data .= ", last_name = '$last_name' ";
		$data .= ", mobile = '$mobile' ";
		$data .= ", address = '$address' ";
		$data .= ", email = '$email' ";
		$data .= ", password = '$password' ";
		$chk = $this->db->query("SELECT * FROM user_info where email = '$email' ")->num_rows;
		if($chk > 0){
			return 2;
			exit;
		}
		$save = $this->db->query("INSERT INTO user_info set ".$data);
		if($save){
			$login = $this->login2();
			return 1;
		}
	}

	function save_settings(){
		extract($_POST);
		$data = " name = '$name' ";
		$data .= ", email = '$email' ";
		$data .= ", contact = '$contact' ";
		$data .= ", about_content = '".htmlentities(str_replace("'","&#x2019;",$about))."' ";
		if($_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/img/'. $fname);
			$data .= ", cover_img = '$fname' ";
		}
		
		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk->num_rows > 0){
			$save = $this->db->query("UPDATE system_settings set ".$data." where id =".$chk->fetch_array()['id']);
		}else{
			$save = $this->db->query("INSERT INTO system_settings set ".$data);
		}
		if($save){
			$query = $this->db->query("SELECT * FROM system_settings limit 1")->fetch_array();
			foreach ($query as $key => $value) {
				if(!is_numeric($key))
					$_SESSION['setting_'.$key] = $value;
			}
			return 1;
		}
	}
	
	function save_category(){
		extract($_POST);
		$data = " name = '$name' ";
		if(empty($id)){
			$save = $this->db->query("INSERT INTO category_list set ".$data);
		}else{
			$save = $this->db->query("UPDATE category_list set ".$data." where id=".$id);
		}
		if($save)
			return 1;
	}
	function delete_category(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM category_list where id = ".$id);
		if($delete)
			return 1;
	}
	function save_menu(){
		extract($_POST);
		$data = " name = '$name' ";
		$data .= ", price = '$price' ";
		$data .= ", category_id = '$category_id' ";
		$data .= ", description = '$description' ";
		if(isset($status) && $status == 'on')
			$data .= ", status = 1 ";
		else
			$data .= ", status = 0 ";

		if($_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			$move = move_uploaded_file($_FILES['img']['tmp_name'],'../assets/img/'. $fname);
			$data .= ", img_path = '$fname' ";
		}
		if(empty($id)){
			$save = $this->db->query("INSERT INTO product_list set ".$data);
		}else{
			$save = $this->db->query("UPDATE product_list set ".$data." where id=".$id);
		}
		if($save)
			return 1;
	}

	function delete_menu(){
		extract($_POST);
		$delete = $this->db->query("DELETE FROM product_list where id = ".$id);
		if($delete)
			return 1;
	}
	function delete_cart(){
		if(isset($_SESSION['login_user_id']) && isset($_GET['id'])){
			$stmt = $this->db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
			$stmt->bind_param("ii", $_GET['id'], $_SESSION['login_user_id']);
			$stmt->execute();
			$stmt->close();
		}
		header('location:'.$_SERVER['HTTP_REFERER']);
	}
	function add_to_cart(){
		extract($_POST);
		$data = " product_id = $pid ";	
		$qty = isset($qty) ? $qty : 1 ;
		$data .= ", qty = $qty ";	
		if(isset($_SESSION['login_user_id'])){
			$data .= ", user_id = '".$_SESSION['login_user_id']."' ";	
		}else{
			$ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
			$data .= ", client_ip = '".$ip."' ";	
		}
		$save = $this->db->query("INSERT INTO cart set ".$data);
		if($save)
			return 1;
	}
	function get_cart_count(){
		if(isset($_SESSION['login_user_id'])){
			$where =" where user_id = '".$_SESSION['login_user_id']."' ";
		}
		else{
			$ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
			$where =" where client_ip = '$ip' ";
		}
		$get = $this->db->query("SELECT sum(qty) as cart FROM cart ".$where);
		if($get->num_rows > 0){
			return $get->fetch_array()['cart'];
		}else{
			return '0';
		}
	}

	function update_cart_qty(){
		extract($_POST);
		$data = " qty = $qty ";
		$save = $this->db->query("UPDATE cart set ".$data." where id = ".$id);
		if($save)
			return 1;	
	}

	function save_order(){
    
    extract($_POST); 
    
    $this->db->begin_transaction();
    
    $upload_dir = '../admin/uploads/proofs/'; 
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); 
    }
    
    $proof_file_path = null;
    
    if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] == 0) {
        $file = $_FILES['proof_file'];
        $fname = 'proof_' . $_SESSION['login_user_id'] . '_' . time() . '_' . basename($file['name']);
        
        $move = move_uploaded_file($file['tmp_name'], $upload_dir . $fname);

        if ($move) {
            $proof_file_path = 'admin/uploads/proofs/' . $fname; 
        } else {
            $_SESSION['alerto'] = 'Error: Failed to move proof of payment file. Check folder permissions (admin/uploads/proofs).';
            $this->db->rollback();
            header("Location: ../index.php?page=checkout&upload_error=1");
            exit;
        }
    } else if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] != 4) { 
        $_SESSION['alerto'] = 'Error: File upload failed with code ' . $_FILES['proof_file']['error'] . '. Check PHP settings.';
        $this->db->rollback();
        header("Location: ../index.php?page=checkout&upload_error=2");
        exit;
    }

    try {
        if (!isset($_SESSION['login_user_id'])) {
            header("Location: ../index.php?page=login"); 
            exit;
        }

        $sql = "INSERT INTO orders (name, address, mobile, email, user_id, proof_file, date_updated) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $full_name = $_POST['first_name'] . " " . $_POST['last_name'];
        
        $stmt->bind_param("ssssis", 
            $full_name, 
            $_POST['address'], 
            $_POST['mobile'], 
            $_POST['email'], 
            $_SESSION['login_user_id'],
            $proof_file_path 
        );
        
        if (!$stmt->execute()) {
             throw new Exception("Order insertion failed: " . $stmt->error);
        }
        
        $order_id = $this->db->insert_id;
        $stmt->close();
        
        $cart_qry = $this->db->prepare("SELECT id, product_id, qty FROM cart WHERE user_id = ?");
        $cart_qry->bind_param("i", $_SESSION['login_user_id']);
        $cart_qry->execute();
        $cart_result = $cart_qry->get_result();
        
        if($cart_result->num_rows == 0) {
             throw new Exception("Cart is empty.");
        }

        while($row = $cart_result->fetch_assoc()){
            $sql2 = "INSERT INTO order_list (order_id, product_id, qty) VALUES (?, ?, ?)";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param("iii", $order_id, $row['product_id'], $row['qty']);
            if (!$stmt2->execute()) {
                 throw new Exception("Order list insertion failed: " . $stmt2->error);
            }
            $stmt2->close();
        }
        $cart_qry->close();

        $delete_cart = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        $delete_cart->bind_param("i", $_SESSION['login_user_id']);
        $delete_cart->execute();
        $delete_cart->close();

        $this->db->commit();
        $_SESSION['alerto'] = 'Order placed successfully! Please wait for admin verification.';
        header("Location: ../index.php?page=my_orders"); 
        exit;
        
    } catch (Exception $e) {
        $this->db->rollback();
       
        if ($proof_file_path && file_exists('../' . $proof_file_path)) {
            unlink('../' . $proof_file_path);
        }

        $_SESSION['alerto'] = 'System Error: ' . $e->getMessage();
        header("Location: ../index.php?page=checkout&error=1");
        exit;
    }
}

function update_order_status(){
    $conn = $this->db; 
    
    $order_id = isset($_POST['order_id']) ? $conn->real_escape_string($_POST['order_id']) : null;
    $new_status = isset($_POST['new_status']) ? $conn->real_escape_string($_POST['new_status']) : null;

    if (empty($order_id) || !is_numeric($new_status)) {
        return json_encode(['status' => 'error', 'message' => 'Missing Order ID or Status data.']);
    }

    $order_id = intval($order_id);
    $new_status = intval($new_status);

    $update_order = $conn->query("
        UPDATE orders 
        SET 
            status = '$new_status', 
            date_updated = NOW() 
        WHERE 
            id = $order_id
    ");

    if (!$update_order) {
        return json_encode(array('status' => 'error', 'message' => 'Order status update failed: ' . $conn->error));
    }
    if ($new_status == 4) { 
        
        $sales_calc_sql = "
            SELECT 
                SUM(ol.qty * p.price) AS total_amount
            FROM 
                order_list ol
            INNER JOIN 
                product_list p ON ol.product_id = p.id
            WHERE 
                ol.order_id = $order_id
        ";
        
        $order_details_query = $conn->query($sales_calc_sql);
        
        $total_amount = 0;
        
        if ($order_details_query === false) {
             return json_encode(array('status' => 'error', 'message' => 'SALES CALCULATION FAILED (SQL ERROR): ' . $conn->error . ' --- SQL: ' . $sales_calc_sql));
        }

        if ($order_details_query->num_rows > 0) {
            $row = $order_details_query->fetch_assoc();
            $total_amount = floatval($row['total_amount'] ?? 0); 
        }

        $check_sales = $conn->query("
            SELECT id FROM sales WHERE order_id = $order_id
        ");

        if ($check_sales->num_rows <= 0 && $total_amount > 0) { 
     
            $sales_insert_sql = "
                INSERT INTO sales (order_id, amount) 
                VALUES ('$order_id', '$total_amount')
            ";
            $save_sales = $conn->query($sales_insert_sql);

            if (!$save_sales) {
                return json_encode(array('status' => 'error', 'message' => 'CRITICAL SALES SAVE FAILED: ' . $conn->error . ' --- SQL: ' . $sales_insert_sql));
            }
        }
    }
    
    return json_encode(array('status' => 'success', 'message' => 'Order status updated successfully.', 'order_id' => $order_id));
}
	function submit_feedback() {
		$user_id = isset($_SESSION['login_user_id']) ? $_SESSION['login_user_id'] : 0;
		$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
		$rating = isset($_POST['rating']) ? $_POST['rating'] : 0;
		$comment = isset($_POST['comment']) ? $_POST['comment'] : '';
		
		if (empty($order_id) || empty($rating) || empty($user_id)) {
			return json_encode(['status' => 'error', 'message' => 'Missing required data.']);
		}

		$this->db->begin_transaction();
		
		try {
			$check_stmt = $this->db->prepare("SELECT id FROM feedback WHERE order_id = ? AND customer_id = ?");
			$check_stmt->bind_param("ii", $order_id, $user_id);
			$check_stmt->execute();
			$check_result = $check_stmt->get_result();
			
			if ($check_result->num_rows > 0) {
				$check_stmt->close();
				return json_encode(['status' => 'error', 'message' => 'You have already submitted feedback for this order.']);
			}
			$check_stmt->close();

			$stmt = $this->db->prepare("INSERT INTO feedback (order_id, customer_id, rating, comment, date_submitted) VALUES (?, ?, ?, ?, NOW())");
			$stmt->bind_param("iiis", $order_id, $user_id, $rating, $comment);
			
			if (!$stmt->execute()) {
				throw new Exception("Failed to insert feedback: " . $stmt->error);
			}
			$stmt->close();
			
			$update_qry = $this->db->prepare("UPDATE orders SET feedback_submitted = 1 WHERE id = ? AND user_id = ?");
			$update_qry->bind_param("ii", $order_id, $user_id);
			
			if (!$update_qry->execute()) {
				throw new Exception("Failed to update orders table: " . $update_qry->error);
			}
			$update_qry->close();
			
			$this->db->commit();
			return json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully.']);
			
		} catch (Exception $e) {
			$this->db->rollback();
			return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
	}

	function cancel_order(){
		extract($_POST);
		
		$update = $this->db->query("UPDATE orders SET status = 3 WHERE id = ".$id);
		if($update){
			return 1;
		} else {
			return 0;
		}
	}
}

?>