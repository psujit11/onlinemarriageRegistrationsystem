<?php require_once('./config.php') ?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition login-page ">
  <script>
    start_loader()
  </script>
  <style>
    body{
      background-image: url('./uploads/cover.jpg');
      background-size:cover;
      background-repeat:no-repeat;
    }
	img#cimg{
		height: 15vh;
		width: 15vh;
		object-fit: scale-down;
		object-position: center;
		border-radius: 100% 100%;
	}
  </style>
  <h1 class="text-center py-5 login-title"><b><?php echo $_settings->info('name') ?></b></h1>
<div class="col-8">
  <!-- /.login-logo -->
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>Sign Up</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">All Fields are required.</p>

      <form id="registration-form" action="" method="post">
          <input type="hidden" name="id">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="firstname" class="control-label">First Name</label>
                    <input type="text" class="form-control" autofocus name="firstname" placeholder="Firstname" required>
                </div>
                <div class="form-group">
                    <label for="Middename" class="control-label">Middle Name</label>
                    <input type="text" class="form-control" name="middlename" placeholder="(optional))">
                </div>
                <div class="form-group">
                    <label for="lastname" class="control-label">Last Name</label>
                    <input type="text" class="form-control" name="lastname" placeholder="Lastname" required>
                </div>
                <div class="form-group">
                    <label for="gender" class="control-label">Gender</label>
                    <select name="gender" id="gender" class="custom-select" required>
                        <option>Male</option>
                        <option>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="contact" class="control-label">Contact #</label>
                    <input type="text" class="form-control" name="contact" placeholder="Contact" required>
                </div>
                <div class="form-group">
                    <label for="address" class="control-label">Address</label>
                    <textarea rows="2" class="form-control" name="address" placeholder="" required></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="control-label">Email</label>
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <label for="password" class="control-label">Password</label>
                    <input type="password" id="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <label for="cpassword" class="control-label">Confirm Password</label>
                    <input type="password" id="cpassword" class="form-control" placeholder="Confirm Password" required>
                </div>
                <div class="form-group">
					<label for="" class="control-label">Avatar</label>
					<div class="custom-file">
		              <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
		              <label class="custom-file-label" for="customFile">Choose file</label>
		            </div>
				</div>
				<div class="form-group d-flex justify-content-center">
					<img src="<?php echo validate_image('') ?>" alt="" id="cimg" class="img-fluid img-thumbnail border bg-black">
				</div>
            </div>
        </div>
        <div class="row">
          <div class="col-8">
            <a href="<?php echo base_url.'admin' ?>">I alredy have an account</a>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Register Acount</button>
          </div>
          <!-- /.col -->
        </div>
      </form>
      <!-- /.social-auth-links -->

      <!-- <p class="mb-1">
        <a href="forgot-password.html">I forgot my password</a>
      </p> -->
      
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>

<script>
  $(document).ready(function(){
    end_loader();
    $('#registration-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
            $('#password,#cpassword').removeClass('is-invalid')
			 $('.err-msg').remove();
             if($('#password').val() != $('#cpassword').val()){
                $('#password,#cpassword').addClass('is-invalid')
                $('#cpassword').trigger('focus')
                var el = $('<div>')
                    el.addClass("alert alert-danger err-msg").text("Password does not match.")
                    el.hide()
                    _this.prepend(el)
                    el.show('slow')
                return false;
             }
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Users.php?f=save_client",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occured",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.replace("./?page=view_registration&id="+resp.id);
					}else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            end_loader()
                    }else{
						alert_toast("An error occured",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})
  })
  function displayImg(input,_this) {
	    if (input.files && input.files[0]) {
	        var reader = new FileReader();
	        reader.onload = function (e) {
	        	$('#cimg').attr('src', e.target.result);
	        }

	        reader.readAsDataURL(input.files[0]);
	    }
	}
</script>
</body>
</html>