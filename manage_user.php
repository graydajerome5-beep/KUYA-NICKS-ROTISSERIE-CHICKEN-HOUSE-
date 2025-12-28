<?php 
include('db_connect.php');
$meta = array();

if(isset($_GET['id'])){
    $user = $conn->query("SELECT * FROM users WHERE id=".$_GET['id']);
    if($user->num_rows > 0){
        $meta = $user->fetch_assoc(); 
    }
}
?>
<div class="container-fluid">
	
	<form action="" id="manage-user">
		<input type="hidden" name="id" value="<?php echo isset($meta['id']) ? $meta['id']: '' ?>">
		
		<div class="form-group">
            <label for="firstname" class="control-label">First Name</label>
            <input type="text" class="form-control" name="firstname" required value="<?php echo isset($meta['firstname']) ? $meta['firstname'] : '' ?>">
        </div>

		<div class="form-group">
            <label for="lastname" class="control-label">Last Name</label>
            <input type="text" class="form-control" name="lastname" required value="<?php echo isset($meta['lastname']) ? $meta['lastname'] : '' ?>">
        </div>
        
		<div class="form-group">
			<label for="username">Username</label>
			<input type="text" name="username" id="username" class="form-control" value="<?php echo isset($meta['username']) ? $meta['username']: '' ?>" required>
		</div>
		
		<div class="form-group">
			<label for="password">Password</label>
			<div class="input-group">
				<input type="password" name="password" id="password" class="form-control" value="">
				<div class="input-group-append">
					<button class="btn btn-outline-secondary" type="button" id="togglePassword">
						<i class="fa fa-eye"></i>
					</button>
				</div>
			</div>
			<?php if(isset($meta['id'])): ?>
			<small>Leave blank if password will not be changed.</small>
			<?php endif; ?>
		</div>
		
		<div class="form-group">
			<label for="type">User Type</label>
			<select name="type" id="type" class="custom-select">
				<option value="1" <?php echo isset($meta['type']) && $meta['type'] == 1 ? 'selected': '' ?>>Admin</option>
				<option value="2" <?php echo isset($meta['type']) && $meta['type'] == 2 ? 'selected': '' ?>>Staff</option>
			</select>
		</div>
	</form>
</div>

<div class="modal-footer">
    <button type="submit" class="btn btn-primary" form="manage-user">Save</button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
</div>

<script>
	$('#togglePassword').click(function() {
		let passwordField = $('#password');
		let type = passwordField.attr('type') === 'password' ? 'text' : 'password';
		passwordField.attr('type', type);
		$(this).find('i').toggleClass('fa-eye fa-eye-slash');
	});

	$('#manage-user').submit(function(e){
		e.preventDefault();
		
		var is_edit = $('[name="id"]').val() != '';
        var password_input = $('[name="password"]');

        if (is_edit && password_input.val() == ''){
            password_input.prop('required', false);
        } else if (!is_edit && password_input.val() == ''){
            password_input.prop('required', true);
        }
		
		start_load()
		$.ajax({
			url:'ajax.php?action=save_user',
			method:'POST',
			data:$(this).serialize(),
			success:function(resp){
				if(resp == 1){
					alert_toast("Data successfully saved",'success')
					setTimeout(function(){
						location.reload()
					},1500)
				} else if (resp == 2) {
                    alert_toast("Username already exists",'error')
                    end_load()
                }
			}
		})
	})

    if($('[name="id"]').val() == ''){
        $('[name="password"]').prop('required', true); 
    }
</script>
