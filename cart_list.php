<?php 
?>

<style>

.card {
    border-radius: 12px !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05) !important; 
    border: 1px solid #d1d1d1 !important; 
    background-color: #ffffff;
}

.sticky .card {
    border-bottom-left-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-bottom: none !important;
}

.cart-item-card {
    border-radius: 0 !important;
    margin-bottom: 0 !important;
    border-left: 1px solid #d1d1d1 !important;
    border-right: 1px solid #d1d1d1 !important;
    border-top: 1px solid #f0f0f0 !important;
}

.col-lg-8 .card:nth-of-type(2) {
    border-top: none !important;
}

.col-lg-8 .card:last-of-type {
    border-bottom-left-radius: 12px !important;
    border-bottom-right-radius: 12px !important;
    border-bottom: 1px solid #d1d1d1 !important;
}

.col-md-4 .card {
    border-radius: 12px !important;
    border: 1px solid #d1d1d1 !important;
}


.card p {
    margin: unset
}
.cart-img {
    width: 70px;
    height: 70px; 
    object-fit: cover;
    border-radius: 5px; 
    border: 1px solid #ddd; 
}
.card img{
    max-width: unset;
    max-height: unset;
}
.cart-item-card .card-body img {
    max-width: 70px;
    max-height: 70px;
}

div.sticky {
    position: -webkit-sticky; 
    position: sticky;
    top: 4.7em;
    z-index: 10;
    background: white
}
.rem_cart{
    position: relative;
    left: unset;
}
.col p.mb-1 {
    margin-bottom: 0.1rem !important;
}
.input-group-sm input.form-control {
    height: calc(1.8rem + 2px); 
    padding: 0 0.2rem;
}
.input-group-sm .btn {
    padding: 0.1rem 0.5rem; 
}
.sticky .card-body {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0ee;
    padding: 10px 15px; 
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
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="sticky">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8"><b>Items</b></div>
                                <div class="col-md-4 text-right"><b>Total</b></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                if(isset($_SESSION['login_user_id'])){
                    $data = "where c.user_id = '".$_SESSION['login_user_id']."' ";	
                }else{
                    $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
                    $data = "where c.client_ip = '".$ip."' ";	
                }
                $total = 0;
                $get = $conn->query("SELECT *,c.id as cid FROM cart c inner join product_list p on p.id = c.product_id ".$data);
                while($row= $get->fetch_assoc()):
                    $total += ($row['qty'] * $row['price']);
                ?>

                <div class="card mb-3 cart-item-card">
                    <div class="card-body">
                        <div class="row d-flex align-items-center">

                            <div class="col-md-8 d-flex align-items-start"> 
                                
                                <div class="col-auto px-0 pt-2">	
                                    <a href="admin/ajax.php?action=delete_cart&id=<?php echo $row['cid'] ?>" class="rem_cart btn btn-sm btn-outline-danger" data-id="<?php echo $row['cid'] ?>"><i class="fa fa-trash"></i></a>
                                </div>
                                
                                <div class="col-auto px-1">	
                                    <img src="assets/img/<?php echo $row['img_path'] ?>" alt="" class="cart-img">
                                </div>	
                                
                                <div class="col px-2">	
                                    <p><b><large><?php echo $row['name'] ?></large></b></p>
                                    <p class='truncate mb-1'> <small>Desc :<?php echo $row['description'] ?></small></p>
                                    <p class="mb-1"> <small>Unit Price :<?php echo number_format($row['price'],2) ?></small></p>
                                    <p class="mb-1"><small>QTY :</small></p>

                                    <div class="input-group input-group-sm" style="max-width: 120px;">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary qty-minus" type="button" data-id="<?php echo $row['cid'] ?>"><span class="fa fa-minus"></span></button>
                                        </div>
                                        <input type="number" readonly value="<?php echo $row['qty'] ?>" min = 1 class="form-control text-center" name="qty" >
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary qty-plus" type="button" id="" data-id="<?php echo $row['cid'] ?>"><span class="fa fa-plus"></span></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 text-right d-flex align-items-center justify-content-end">
                                <b><large><?php echo number_format($row['qty'] * $row['price'],2) ?></large></b>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <?php endwhile; ?>
            </div>
            <div class="col-md-4">
                <div class="sticky">
                    <div class="card">
                        <div class="card-body">
                            <p><large>Total Amount</large></p>
                            <hr>
                            <p class="text-right"><b><?php echo number_format($total,2) ?></b></p>
                            <hr>
                            <div class="text-center">
                                <button class="btn btn-block btn-outline-dark" type="button" id="checkout">Proceed to Checkout</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
    .card p {
        margin: unset
    }
    .cart-img {
        width: 100px; 
        height: 100px; 
        object-fit: cover; 
        border-radius: 5px; 
    }
    .card img{
        max-width: unset;
        max-height: unset;
    }
    .cart-item-card .card-body img {
        max-width: 100px;
        max-height: 100px;
    }

    div.sticky {
        position: -webkit-sticky; 
        position: sticky;
        top: 4.7em;
        z-index: 10;
        background: white
    }
    .rem_cart{
        position: relative;
        left: unset;
    }
    .col p.mb-1 {
        margin-bottom: 0.1rem !important;
    }
</style>
<script>
    
    $('.view_prod').click(function(){
        uni_modal_right('Product','view_prod.php?id='+$(this).attr('data-id'))
    })
    $('.qty-minus').click(function(){
    var qty = $(this).parent().siblings('input[name="qty"]').val();
    update_qty(parseInt(qty) -1,$(this).attr('data-id'))
    if(qty == 1){
        return false;
    }else{
         $(this).parent().siblings('input[name="qty"]').val(parseInt(qty) -1);
    }
    })
    $('.qty-plus').click(function(){
        var qty =  $(this).parent().siblings('input[name="qty"]').val();
             $(this).parent().siblings('input[name="qty"]').val(parseInt(qty) +1);
    update_qty(parseInt(qty) +1,$(this).attr('data-id'))
    })
    function update_qty(qty,id){
        start_load()
        $.ajax({
            url:'admin/ajax.php?action=update_cart_qty',
            method:"POST",
            data:{id:id,qty},
            success:function(resp){
                if(resp == 1){
                    load_cart()
                    end_load()
                }
            }
        })

    }
    $('#checkout').click(function(){
        if('<?php echo isset($_SESSION['login_user_id']) ?>' == 1){
            location.replace("index.php?page=checkout")
        }else{
            uni_modal("Checkout","login.php?page=checkout")
        }
    })
</script>