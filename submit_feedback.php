<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'admin/db_connect.php'; 

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request or required data is missing.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $customer_id = $_SESSION['login_user_id'] ?? null;
    if (!$customer_id) {
        $response = ['status' => 'error', 'message' => 'User not logged in. Please log in to submit feedback.'];
        echo json_encode($response);
        exit;
    }

    $rating = $_POST['rating'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    $order_id = $_POST['order_id'] ?? null; 
    $product_id = $_POST['product_id'] ?? null; 
    $img_path = ''; 

    if ($rating < 1 || empty($comment)) {
        $response = ['status' => 'error', 'message' => 'Rating and comment are required.'];
        echo json_encode($response);
        exit;
    }
    
    if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
        $target_dir = "assets/uploads/reviews/"; 
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        $imageFileType = strtolower($file_extension);
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $response = ['status' => 'error', 'message' => 'Sorry, only JPG, JPEG, and PNG files are allowed for image upload.'];
            echo json_encode($response);
            exit;
        }

        if (move_uploaded_file($_FILES['img']['tmp_name'], $target_file)) {
            $img_path = $new_filename; 
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to upload image. Check folder permissions.'];
            echo json_encode($response);
            exit;
        }
    }
    
    if (!empty($product_id)) {
        
        try {
            $stmt = $conn->prepare("INSERT INTO feedback (product_id, customer_id, rating, comment, img_path, date_submitted) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiiss", $product_id, $customer_id, $rating, $comment, $img_path);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert product feedback: " . $stmt->error);
            }
            $stmt->close();

            $response = ['status' => 'success', 'message' => 'Product Review submitted successfully.', 'type' => 'product'];
            
        } catch (Exception $e) {
            $response = ['status' => 'error', 'message' => "SQL Error (Product): " . $e->getMessage()];
        }
    
    } elseif (!empty($order_id)) {
        
        $check_qry = $conn->query("SELECT id FROM feedback WHERE order_id = '{$order_id}' AND customer_id = '{$customer_id}'");
        if ($check_qry->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'You have already submitted feedback for this order.'];
            echo json_encode($response);
            exit;
        }

        $conn->begin_transaction();
        try {
            
            $stmt = $conn->prepare("INSERT INTO feedback (order_id, customer_id, rating, comment, img_path, date_submitted) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiiss", $order_id, $customer_id, $rating, $comment, $img_path);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert order feedback: " . $stmt->error);
            }
            $stmt->close();
            
            $update_qry = $conn->prepare("UPDATE orders SET feedback_submitted = 1 WHERE id = ? AND user_id = ?");
            $update_qry->bind_param("ii", $order_id, $customer_id);
            
            if (!$update_qry->execute()) {
                throw new Exception("Failed to update orders table: " . $update_qry->error);
            }
            $update_qry->close();
            
            $conn->commit();
            $response = ['status' => 'success', 'message' => 'Order Feedback submitted successfully.', 'type' => 'order'];
            
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['status' => 'error', 'message' => "SQL Error (Order): " . $e->getMessage()];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Neither order ID nor product ID was provided.'];
    }
}

echo json_encode($response);
?>