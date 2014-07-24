<?php

// set the session cookie parameters so the cookie is only valid for this game
$parts = pathinfo($_SERVER['REQUEST_URI']);

$path = $parts['dirname'];
if (empty($parts['extension'])) {
	$path .= '/'.$parts['basename'];
}
$path = str_replace('\\', '/', $path).'/';

session_set_cookie_params(0, $path);
@session_start( );

require_once 'includes/config.inc.php';
require_once 'includes/html.inc.php';
require 'includes/login.inc.php';

/* invalid password flag */
$isInvalidPassword = false;

/* check if submitting opponents login information */
if ( isset($_POST['opponentsID']) )
{
	$opponentsID = sani($_POST['opponentsID']);
	$opponentsUsername = $_POST['opponentsUsername'];

	/* get opponents password from DB */
	$query = "
		SELECT p_password
		FROM ".T_PLAYER."
		WHERE p_id = '{$opponentsID}'
	";
	$dbPassword = $mysql->fetch_value($query, __LINE__, __FILE__);

	/* check to see if supplied password matched that of the DB */
	if ($dbPassword == substr($_POST['pwdPassword'],5))
	{
		$_SESSION['shared'] = true;
		$_SESSION['game_id'] = $_POST['game_id'];

		/* load game */
		header('Location: ./chess.php');
	}
	else // password is invalid, set flag to true
		$isInvalidPassword = true;
}
else /* else user is arriving here for the first time */
{
	/* get the players associated with this game */
	$query = "
		SELECT g_white_player_id
			, g_black_player_id
		FROM ".T_GAME."
		WHERE g_id = '{$_POST['game_id']}'
	";
	$players = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	/* determine which one is the opponent of the player logged in */
	if ($players['g_white_player_id'] == $_SESSION['player_id'])
	{
		$opponentsID = $players['g_black_player_id'];
	}
	else
	{
		$opponentsID = $players['g_white_player_id'];
	}

	/* get the opponents information */
	$query = "
		SELECT p_username
		FROM ".T_PLAYER."
		WHERE p_id = '{$opponentsID}'
	";
	$opponentsUsername = $mysql->fetch_value($query, __LINE__, __FILE__);
}

if ( $isInvalidPassword )
{
	$errMsg = "!! ERROR !!<br />Invalid Password. Try again.";
}

echo get_header(null, 'Opponent Login', $head_extra);


	if ( isset($errMsg) )
	{
		echo "<h2 class=\"notice\">" . $errMsg . "</h2>\n";
	}
?>
		<div id="notes">
			<div id="date"><?php echo date($CFG_LONGDATE); ?></div>
			<p>Here your opponent can input thier password to enable you to both play on the same computer.</p>
		</div>
		<form id="content" action="opppass.php" method="post" name="form">
				<h2>Enter password for <?php echo $opponentsUsername; ?></h2>
				<input type="password" name="pwdPassword" id="pwdPassword" class="inputbox" /> Password<br />
				<input type="hidden" name="opponentsUsername" value="<?php echo $opponentsUsername; ?>" />
				<input type="hidden" name="opponentsID" value="<?php echo $opponentsID; ?>" />
				<input type="hidden" name="game_id" value="<?php echo $_POST['game_id']; ?>" />
				<input type="submit" value="Continue" />
				<input type="button" value="Back" onClick="window.open('index.php', '_self')"/>
		</form>
		<div id="footerspacer">&nbsp;</div>
		<div id="footer">&nbsp;</div>
	</div>
</body>
</html>