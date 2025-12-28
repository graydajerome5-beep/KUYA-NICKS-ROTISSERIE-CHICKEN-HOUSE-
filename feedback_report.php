<?php

include 'db_connect.php';

$query = "
    SELECT 
        f.*, 
        u.user_id AS customer_id, 
        u.first_name, 
        u.last_name 
    FROM 
        feedback f
    JOIN 
        user_info u ON f.customer_id = u.user_id 
    ORDER BY 
        f.date_submitted DESC
";
$feedback_result = $conn->query($query);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm"> 
                <div class="card-header">
                    <h3 class="card-title">Customer Feedback Report</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Customer ID</th> 
                                <th>Customer Name</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            
                            if (!$feedback_result) {
                                
                                echo '<tr><td colspan="5" class="text-center">Error fetching feedback: ' . $conn->error . '</td></tr>';
                            } elseif ($feedback_result->num_rows > 0) {
                                while ($row = $feedback_result->fetch_assoc()):
                                    
                                    
                                    $customer_full_name = ucwords($row['first_name'] . ' ' . $row['last_name']);
                                    
                                    
                                    $stars = str_repeat('★', $row['rating']);
                                    
                            ?>
                            <tr>
                                <td>
                                 <?php echo $row['customer_id']; ?><br>

                                </td>
                                <td>
                                    <?php echo $customer_full_name; ?>
                                </td>
                                <td>
                                    <span style="color: #FFC107; font-size: 1.2em;"><?php echo $stars; ?></span>
                                </td>
                                <td><?php echo $row['comment']; ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['date_submitted'])); ?></td> 
                            </tr>
                            <?php
                                endwhile;
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No feedback found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>