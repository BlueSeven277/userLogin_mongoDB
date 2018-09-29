<?php if (!defined('TMPL_DIR')) return; ?>

<form id="inputArea" action="index.php?action=signup" method="post">

        <label>Email</label>
        <input type="text" name="email" value="<?php echo $HTML['email'];?>" />
        <span><?php echo $HTML['email_error'];?></span>

        <label>Password</label>
        <input type="password" name="password" autocomplete="off" value="<?php echo $HTML['password'];?>" />

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" autocomplete="off" value="<?php echo $HTML['confirm_password'];?>" />
		<span><?php echo $HTML['confirm_password_error'];?></span>


        <label>Full Name</label>
        <input type="text" name="name" value="<?php echo $HTML['name'];?>" />
        <span><?php echo $HTML['name_error'];?></span>
        
                
        <label>City</label>
        <input type="text" name="city" value="<?php echo $HTML['city'];?>" />
        <span><?php echo $HTML['city_error'];?></span>

        <label>Country</label>
	<select name="countryID"><?php echo $HTML['country_options_escape'];?></select>
        <span><?php echo $HTML['countryID_error'];?></span>

    <span><?php echo $HTML['signup_error'];?></span>

	<input class="submit" type="submit" value="Sign Up" />
	<input type="hidden" name="submitted" value="yes" />
		
	<div class="user">
		Registered User?  <a href="index.php?action=login" target="_self">Login here</a>
	</div>

</form>
