<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

if(isset($_POST['checkout']) && !empty($_POST['cart'])) {
    $cart = $_POST['cart']; 

    $stmt = $conn->prepare("INSERT INTO orders (status, date_created) VALUES (?, NOW())");
    $walkin_status = 0;
    $stmt->bind_param("i", $walkin_status);
    $stmt->execute();
    $order_id = $stmt->insert_id; 
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO order_list (order_id, product_id, qty) VALUES (?, ?, ?)");
    foreach($cart as $item){
        $stmt->bind_param("iii", $order_id, $item['id'], $item['qty']);
        $stmt->execute();
    }
    $stmt->close();

    $total_amount = 0;
    foreach($cart as $item){
        $total_amount += $item['price'] * $item['qty'];
    }

    $stmt = $conn->prepare("INSERT INTO sales (order_id, amount, date_created) VALUES (?, ?, NOW())");
    $stmt->bind_param("id", $order_id, $total_amount);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Checkout successful!');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Walk-in</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .pos-container { display: flex; gap: 1rem; }
        .product-list { width: 65%; display: grid; grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); gap: 1rem; }
        .product-card { border:1px solid #ddd; border-radius:10px; overflow:hidden; padding:0.5rem; background:#fff; display:flex; flex-direction:column; justify-content:space-between; }
        .product-card img { width:100%; height:150px; object-fit:cover; border-radius:5px; }
        .not-available { filter:grayscale(100%); opacity:0.6; pointer-events:none; }
        .cart { width:35%; border:1px solid #ddd; border-radius:10px; padding:1rem; height:80vh; overflow-y:auto; }
        .cart-item { display:flex; justify-content:space-between; margin-bottom:0.5rem; align-items:center; }
        .cart-item button { font-size:0.8rem; margin-left:0.2rem; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">POS Walk-in</h2>
    <div class="pos-container">
        <div class="product-list">
            <?php
            $products = $conn->query("SELECT * FROM product_list ORDER BY name ASC");
            while($row = $products->fetch_assoc()):
            ?>
            <div class="product-card <?php echo ($row['status']==0)?'not-available':'' ?>" 
                 data-id="<?php echo $row['id'] ?>" 
                 data-name="<?php echo $row['name'] ?>" 
                 data-price="<?php echo $row['price'] ?>">
                <img src="../assets/img/<?php echo $row['img_path'] ?>" alt="Product">
                <h5 class="mt-2"><?php echo $row['name'] ?></h5>
                <p>₱<?php echo number_format($row['price'],2) ?></p>
                <button class="btn btn-sm btn-success add-to-cart" <?php echo ($row['status']==0)?'disabled':'' ?>>Add</button>
            </div>
            <?php endwhile; ?>
        </div>

        <div class="cart">
            <h4>Cart</h4>
            <div id="cart-items"></div>
            <hr>
            <h5>Total: ₱<span id="cart-total">0.00</span></h5>
            <button class="btn btn-primary w-100 mt-2" id="checkout-btn">Checkout</button>
        </div>
    </div>
</div>

<script>
let cart = [];

$('.add-to-cart').click(function(){
    let card = $(this).closest('.product-card');
    let id = card.data('id'), name = card.data('name'), price = parseFloat(card.data('price'));

    let item = cart.find(i => i.id == id);
    if(item){ item.qty += 1; } 
    else { cart.push({id,name,price,qty:1}); }
    updateCart();
});

function updateCart(){
    let cartItems = $('#cart-items');
    cartItems.html('');
    let total = 0;
    cart.forEach((item, index)=>{
        total += item.price * item.qty;
        cartItems.append(`
            <div class="cart-item">
                <span>${item.name} x ${item.qty}</span>
                <span>₱${(item.price*item.qty).toFixed(2)}</span>
                <div>
                    <button class="btn btn-sm btn-secondary decrease" data-index="${index}">-</button>
                    <button class="btn btn-sm btn-secondary increase" data-index="${index}">+</button>
                    <button class="btn btn-sm btn-danger remove" data-index="${index}"><i class="fa fa-trash"></i></button>
                </div>
            </div>
        `);
    });
    $('#cart-total').text(total.toFixed(2));
}

$(document).on('click', '.increase', function(){
    let index = $(this).data('index');
    cart[index].qty += 1;
    updateCart();
});

$(document).on('click', '.decrease', function(){
    let index = $(this).data('index');
    if(cart[index].qty > 1){ cart[index].qty -= 1; }
    else { cart.splice(index,1); }
    updateCart();
});

$(document).on('click', '.remove', function(){
    let index = $(this).data('index');
    cart.splice(index,1);
    updateCart();
});

$('#checkout-btn').click(function(){
    if(cart.length == 0){ alert('Cart is empty!'); return; }

    $.post('', {checkout:1, cart:cart}, function(response){
        alert('Checkout successful!');
        cart = [];
        updateCart();
        location.reload(); /
    });
});
</script>
</body>
</html>
