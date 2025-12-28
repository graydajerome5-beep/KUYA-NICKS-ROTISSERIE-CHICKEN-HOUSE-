<?php session_start() ?>
<style>
  
    #back_to_login_btn {
        position: absolute; 
        top: -50px; 
        right: 10px; 
        z-index: 9999; 
        width: 25px; 
        height: 25px; 
        background-color: #000; 
        color: #fff !important; 
        border-radius: 5px; 
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    
	#uni_modal .modal-footer{
		display:none;
	}
    
    .toggle-password:focus {
        box-shadow: none !important;
    }

        #uni_modal .modal-header .modal-title {
         
    }
    
    #uni_modal .modal-content {
        position: relative;
    }
</style>
<div class="container-fluid">
    <button class="btn btn-link p-0" type="button" onclick="openLoginModal()" title="Back to Login" id="back_to_login_btn">
        <i class="fa fa-arrow-left"></i> 
    </button>
    
	<form action="" id="signup-frm">
		<div class="form-group">
			<label for="" class="control-label">Firstname</label>
			<input type="text" name="first_name" required="" class="form-control">
		</div>
		<div class="form-group">
			<label for="" class="control-label">Lastname</label>
			<input type="text" name="last_name" required="" class="form-control">
		</div>
		<div class="form-group">
			<label for="" class="control-label">Contact Number</label>
			<input type="tel" name="mobile" id="mobile_contact" required="" class="form-control" maxlength="11">
		</div>
		<div class="form-group">
			<label for="" class="control-label">Complete Address</label>
			<textarea cols="30" rows="3" name="address" required="" class="form-control"></textarea>
		</div>
		<div class="form-group">
			<label for="" class="control-label">Email</label>
			<input type="email" name="email" required="" class="form-control">
		</div>
		
		<div class="form-group">
			<label for="" class="control-label">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="password_field" required="" class="form-control">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password_field">
                        <i class="fa fa-eye" id="toggle_icon"></i>
                    </button>
                </div>
            </div>
		</div>
		<button class="button btn btn-info btn-sm">Create</button>
	</form>
</div>

<script>
    function openLoginModal(){
        uni_modal("Login", "login.php"); 
    }
	
    $('.toggle-password').click(function(){
        var target_id = $(this).data('target');
        var input = $(target_id);
        var icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash'); 
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye'); 
        }
    });
      $('#signup-frm').submit(function(e){
		e.preventDefault()
		
		var contact = $('#mobile_contact').val(); 
		
		if($(this).find('.alert-danger').length > 0 )
			$(this).find('.alert-danger').remove();
		
		if(contact.length != 11){
			$('#signup-frm').prepend('<div class="alert alert-danger">Contact number must be exactly 11 digits (e.g., 09xxxxxxxxx).</div>');
			$('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');
			return false; 		}

		
		if(!$.isNumeric(contact)){
			$('#signup-frm').prepend('<div class="alert alert-danger">Contact number must only contain numbers.</div>');
			$('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');
			return false; 		}

		
		if(!contact.startsWith('09')){
			$('#signup-frm').prepend('<div class="alert alert-danger">Contact number must start with 09 (Philippine Mobile Format).</div>');
			$('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');
			return false; 		}

		$('#signup-frm button[type="submit"]').attr('disabled',true).html('Saving...');
		
		$.ajax({
			url:'admin/ajax.php?action=signup',
			method:'POST',
			data:$(this).serialize(),
			error:err=>{
				console.log(err)
		        $('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');

			},
			success:function(resp){
				if(resp == 1){
					location.href ='<?php echo isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php?page=home' ?>';
				} else if (resp == 2) {
                    $('#signup-frm').prepend('<div class="alert alert-danger"><b>Email Error:</b> The email address is already registered. Please login or use a different email.</div>');
                    $('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');
                } else if (resp == 3) {
                    $('#signup-frm').prepend('<div class="alert alert-danger"><b>Email Error:</b> The email address is already registered. Please login or use a different email.</div>');
                    $('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');
                } else {
					$('#signup-frm').prepend('<div class="alert alert-danger">An unknown error occurred.</div>');
					$('#signup-frm button[type="submit"]').removeAttr('disabled').html('Create');
				}
			}
		})
	})
</script>