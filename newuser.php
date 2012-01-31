<?php

// set the session cookie parameters so the cookie is only valid for this game
$parts = pathinfo($_SERVER['REQUEST_URI']);

$path = $parts['dirname'];
if (empty($parts['extension'])) {
	$path .= $parts['basename'];
}
$path = str_replace('\\', '/', $path).'/';

session_set_cookie_params(0, $path);
@session_start( );

/* load settings */
require_once 'includes/config.inc.php';
require_once 'includes/html.inc.php';

if (false == $CFG_NEWUSERS)
{
	die("Not Authorized!");
}

// set a token
$_SESSION['token'] = md5(uniqid(rand( ),true));

$head_extra = '
	<script type="text/javascript" src="javascript/md5.js"></script>
	<script type="text/javascript">
		function validateForm( )
		{
			// check that all name fields are filled
			if ( document.userdata.txtFirstName.value.match(/^\s*$/)
						|| document.userdata.txtLastName.value.match(/^\s*$/)
						|| document.userdata.txtUsername.value.match(/^\s*$/)
						|| document.userdata.pwdPassword.value.match(/^\s*$/)
						|| document.userdata.pwdPassword2.value.match(/^\s*$/) )
			{
				alert("Sorry, all personal info fields are required and must be filled out.\nAll white space passwords are not allowed.");
				return false;
			}

			// if both are blank, they are still equal
			if (document.userdata.pwdPassword.value == document.userdata.pwdPassword2.value)
			{
				if (document.userdata.pwdPassword.value.substring(0,5) != \'!md5!\')
				{
					document.userdata.pwdPassword.value = \'!md5!\' + hex_md5(document.userdata.pwdPassword.value);
					document.userdata.pwdPassword2.value = \'!md5!\' + hex_md5(document.userdata.pwdPassword2.value);
				}
				return true;
			}
			else
			{
				alert("Sorry, the two password fields don\'t match.  Please try again.");
				return false;
			}
		}
	</script>
';

echo get_header(null, 'Create New User', $head_extra)
?>

		<div id="notes">
			<div id="date"><?php echo date($CFG_LONGDATE); ?></div>
			<p>Welcome to WebChess!</p>
			<p>You must remember your username and password to be able to gain access to WebChess.</p>
		</div>
		<form id="content" name="userdata" method="post" action="index.php" onsubmit="return validateForm( );">
			<h2>Registration</h2>
			<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
			<input name="txtFirstName" type="text" class="inputbox" maxlength="20" tabindex="1" /> First Name<br />
			<input name="txtLastName" type="text" class="inputbox" maxlength="20" tabindex="2" /> Last Name<br />
			<input name="txtUsername" type="text" class="inputbox" maxlength="20" tabindex="3" /> Username<br />
			<input name="pwdPassword" type="password" class="inputbox" tabindex="4" /> Password<br />
			<input name="pwdPassword2" type="password" class="inputbox" tabindex="5" /> Password Confirmation<br />
<?php if ($CFG_USEEMAIL) { ?>

			<input type="text" class="inputbox" name="txtEmail" maxlength="50" tabindex="6" /> Email Address<br />
			<div class="instruction">Enter a valid email address if you would like to be notified when your opponent makes a move. Leave blank otherwise.</div>
<?php } ?>

			<input type="submit" name="register" class="button" value="Register" tabindex="7" />
		</form>
		<div id="footerspacer">&nbsp;</div>
		<div id="footer">&nbsp;</div>
	</div>
</body>
</html>