<?php
if (!isset($_SESSION)) {
    session_start();
}

include 'admin/db_connect.php';

$product_id = $conn->real_escape_string($_GET['id']);

$qry = $conn->query("SELECT * FROM product_list WHERE id = '$product_id'")->fetch_array();

$reviews_query = $conn->query("
    SELECT f.*, CONCAT(u.firstname, ' ', u.lastname) AS reviewer_name 
    FROM `feedback` f 
    LEFT JOIN `users` u ON f.customer_id = u.id 
    WHERE f.product_id = '$product_id' 
    ORDER BY f.date_submitted DESC
");

$average_rating_query = $conn->query("
    SELECT AVG(rating) AS avg_rating, COUNT(id) AS total_reviews 
    FROM `feedback` 
    WHERE product_id = '$product_id'
");

$rating_data = $average_rating_query->fetch_assoc();
$average_rating = round($rating_data['avg_rating'], 1);
$total_reviews = $rating_data['total_reviews'];

$is_logged_in = isset($_SESSION['login_user_id']);
$customer_id = $_SESSION['login_user_id'] ?? 0;
?>

<div class="container-fluid">
    <div class="card">
        <img src="assets/img/<?php echo htmlspecialchars($qry['img_path']); ?>" 
             class="card-img-top" 
             alt="Product Image" 
             style="max-height: 250px; object-fit: cover;">
        
        <div class="card-body">
            <h4 class="card-title text-dark">
                <b><?php echo htmlspecialchars($qry['name']); ?></b>
            </h4>

            <div class="mb-1">
                <p class="text-muted mb-0">
                    Price: <b>₱<?php echo number_format($qry['price'], 2); ?></b>
                </p>
            </div>

            <div class="mb-2" style="font-size: 15px;">
                <span style="color: gold;">
                <?php
                    $full_stars = floor($average_rating);
                    $has_half_star = ($average_rating - $full_stars) >= 0.5;

                    echo str_repeat('<i class="fa fa-star"></i>', $full_stars);

                    if ($has_half_star) {
                        echo '<i class="fa fa-star-half-alt"></i>';
                    }

                    echo str_repeat('<i class="far fa-star"></i>', 5 - $full_stars - ($has_half_star ? 1 : 0));
                ?>
                </span>

                <span class="text-muted" style="font-size: 13px;">
                    (<?php echo $average_rating; ?> average based on <?php echo $total_reviews; ?> reviews)
                </span>
            </div>

            <p class="card-text truncate">
                <?php echo nl2br(htmlspecialchars($qry['description'])); ?>
            </p>

            <?php if ($is_logged_in): ?>
            <div class="form-group mt-3">
                <div class="row align-items-center">
                    <div class="col-md-3"><label>Qty</label></div>
                    <div class="input-group col-md-9">
                        <div class="input-group-prepend">
                            <button class="btn btn-outline-secondary" type="button" id="qty-minus">
                                <span class="fa fa-minus"></span>
                            </button>
                        </div>
                        <input type="number" readonly value="1" min="1" 
                               class="form-control text-center" name="qty">
                        <div class="input-group-append">
                            <button class="btn btn-outline-dark" type="button" id="qty-plus">
                                <span class="fa fa-plus"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-2">
                <button class="btn btn-dark btn-block" id="add_to_cart_modal">
                    <i class="fa fa-cart-plus"></i> Add to Cart
                </button>
            </div>

            <?php else: ?>
            <div class="text-center mt-3">
                <a class="btn btn-warning btn-block" href="javascript:void(0)" id="login_to_order_modal">
                    <i class="fa fa-sign-in-alt"></i> Login to Order
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header bg-dark text-white">
            <h4><i class="fa fa-comments"></i> Customer Reviews (<?php echo $total_reviews; ?>)</h4>
        </div>

        <div class="card-body">
            <div id="review-list">

                <?php if ($reviews_query->num_rows > 0): ?>
                    <?php while ($row = $reviews_query->fetch_assoc()): ?>
                    <div class="review-item border-bottom py-3">
                        
                        <div class="mb-1">
                            <span style="color: gold; font-size: 1.2em;">
                                <?php echo str_repeat('<i class="fa fa-star"></i>', $row['rating']); ?>
                                <?php echo str_repeat('<i class="far fa-star"></i>', 5 - $row['rating']); ?>
                            </span>
                        </div>

                        <small class="text-muted">
                            By: <b><?php echo htmlspecialchars($row['reviewer_name'] ?: "Unknown Customer"); ?></b>
                            on <?php echo date("F d, Y", strtotime($row['date_submitted'])); ?>
                        </small>

                        <?php if (!empty($row['img_path'])): ?>
                        <div class="mt-2">
                            <img src="assets/uploads/reviews/<?php echo htmlspecialchars($row['img_path']); ?>" 
                                 alt="Review Image" 
                                 style="max-width: 100px; height: auto; border: 1px solid #ccc; border-radius: 5px;">
                        </div>
                        <?php endif; ?>

                        <p class="mt-1 mb-0">
                            <?php echo nl2br(htmlspecialchars($row['comment'])); ?>
                        </p>
                    </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="alert alert-warning">
                        No reviews yet. Be the first to give feedback!
                    </div>
                <?php endif; ?>

            </div>

            <?php if ($is_logged_in): ?>
            <hr class="mt-4">

            <div class="mb-4 p-3 border rounded">
                <h5>Leave Your Review</h5>

                <form id="product-review-form" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">

                    <div class="form-group">
                        <label>Rating (Stars)</label>
                        <select name="rating" class="form-control" required>
                            <option value="5">★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                            <option value="3">★★★☆☆ (3 Stars)</option>
                            <option value="2">★★☆☆☆ (2 Stars)</option>
                            <option value="1">★☆☆☆☆ (1 Star)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="comment" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Upload Image (Optional)</label>
                        <input type="file" name="img" class="form-control-file" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mt-2">Submit Review</button>
                </form>
            </div>

            <?php else: ?>
            <hr class="mt-4">
            <div class="alert alert-info text-center">
                <a href="javascript:void(0)" id="login_to_review">Login</a> to leave a review.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.card-img-top {
    border-bottom: 1px solid #dee2e6;
}
#uni_modal_right .modal-footer {
    display: none;
}
</style>

<script>

$('#login_to_order_modal').click(function(){
    $('#login_now').trigger('click');
    $('#uni_modal_right').modal('hide');
});
$('#login_to_review').click(function(){
    $('#login_now').trigger('click');
    $('#uni_modal_right').modal('hide');
});

$('#qty-minus').click(function(){
    var qty = $('input[name="qty"]').val();
    if (qty > 1) $('input[name="qty"]').val(parseInt(qty) - 1);
});
$('#qty-plus').click(function(){
    var qty = $('input[name="qty"]').val();
    $('input[name="qty"]').val(parseInt(qty) + 1);
});

$('#add_to_cart_modal').click(function(){
    start_load();
    $.ajax({
        url: 'admin/ajax.php?action=add_to_cart',
        method: 'POST',
        data: { pid: '<?php echo $_GET['id']; ?>', qty: $('[name="qty"]').val() },
        success: function(resp){
            if (resp == 1){
                alert_toast("Order successfully added to cart");
                $('.item_count').html(
                    parseInt($('.item_count').html()) + parseInt($('[name="qty"]').val())
                );
                $('.modal').modal('hide');
                end_load();
            }
        }
    });
});

$('#product-review-form').submit(function(e){
    e.preventDefault();
    start_load();

    var formData = new FormData($(this)[0]);

    $.ajax({
        url: 'submit_feedback.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        success: function(resp){
            if (resp.status == 'success') {
                alert_toast(resp.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                alert_toast(resp.message, 'error');
            }
            end_load();
        },
        error: function(){
            alert_toast("Error submitting review.", 'error');
            end_load();
        }
    });
});
</script>
