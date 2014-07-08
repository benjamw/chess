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

require_once 'includes/config.inc.php';

?>
<!doctype html>
<html lang="en">
<head>

	<title>WebChess :: Send Mailinglist Message</title>

	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Content-Style-Type" content="text/css" />

	<link rel="stylesheet" type="text/css" media="screen" href="css/layout.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/<?php echo $CFG_COLOR_CSS; ?>" />

<?php

$id = $_SESSION['player_id'];

if ( isset($_POST['newMessage']) && $_SESSION['is_admin'] )
{
		$msgsubject = 'WebChess Announcement: ' . $_POST['txtsubject'];
		$msgtext = $_POST['txtMessage'] . "\n\n"
						 . "\n\n----------------------------------------------\n"
						 . "This message has been sent by a WebChess\n"
						 . 'Administrator and should not be replied to.';

		$headers = "From: {$CFG_MAILADDRESS}\r\n";

		$query = "
			SELECT p_email
			FROM ".T_PLAYER."
		";
		$result = $mysql->fetch_array($query, __LINE__, __FILE__);
		$list = '';

		foreach ($result as $email)
		{
			$list .= ('' != $email['p_email']) ? $email['p_email'].',' : '';
		}
		substr($list,0,-1);

		$headers .= "BCC: {$list}\r\n";

		call("mail($CFG_MAILADDRESS, $msgsubject, ".stripslashes($msgtext).", $headers)");
		mail($CFG_MAILADDRESS, $msgsubject, stripslashes($msgtext), $headers);

		if ( ! DEBUG) {
		?>
		<script language="javascript">
				window.close( )
		</script>
		<?php
		}
}
?>
	</head>

	<body>
		<div align="center">
			<br /><br />
			<form action="massmail.php" method="post" name="FormName">
				Mail Subject:<br />
				<input type="text" name="txtsubject" size="54" border="0"><br />
				<br />
				Mail Text:<br />
				<textarea name="txtMessage" rows="10" cols="52" tabindex="1"></textarea><br /><br />
				<input type="submit" name="newMessage" value="Send&nbsp;Mail" border="0">
			</form>
		</div>
	</body>
</html>