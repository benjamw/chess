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

//******************************************************************************
//  delete inactive accounts
//******************************************************************************

// (this function is here to take some of the load
// off of the index page, although it won't be run as often)
if (0 != $CFG_EXPIREUSERS)
{
	// make sure the 'unused' player is not currently in a game
	$query = "
		SELECT DISTINCT g_white_player_id
		FROM ".T_GAME."
	";
	$white_players = $mysql->fetch_value_array($query, __LINE__, __FILE__);

	$query = "
		SELECT DISTINCT g_black_player_id
		FROM ".T_GAME."
	";
	$black_players = $mysql->fetch_value_array($query, __LINE__, __FILE__);

	$player_ids = array_merge($white_players, $black_players);
	$player_ids = array_unique($player_ids);
	$player_ids[] = 0;
	$player_id_list = implode(',', $player_ids);
	call('player_ids');

	// delete unused accounts
	$query = "
		DELETE FROM ".T_PLAYER."
		WHERE p_wins = 0
			AND p_draws = 0
			AND p_losses = 0
			AND p_id NOT IN ({$player_id_list})
			AND DATE_ADD(p_created, INTERVAL {$CFG_EXPIREUSERS} DAY) <= NOW( )
	";
	$mysql->query($query, __LINE__, __FILE__);
}
//*/

// count how many current users there are for testing below
$query = "
	SELECT COUNT(*)
	FROM ".T_PLAYER."
";
$numPlayers = $mysql->fetch_value($query, __LINE__, __FILE__);

// set a token
$_SESSION['token'] = md5(uniqid(rand( ), true));

$head_extra = '
	<script type="text/javascript">
		window.onload = function( )
		{
			document.loginForm.txtUsername.focus( );
			document.loginForm.txtUsername.select( );
		}
	</script>
';

echo get_header(null, 'Login', $head_extra);

?>
		<div id="notes">
			<div id="date"><?php echo date($CFG_LONGDATE); ?></div>
			<p><strong>Welcome to WebChess</strong></p>
			<p>Please enter a valid username and password to enter</p>
		</div>
		<div id="content">
			<h2>Login</h2>
			<noscript class="notice ctr">
				Warning! Javascript must be enabled for proper operation of WebChess.
			</noscript>
			<form name="loginForm" id="loginForm" method="post" action="index.php">
				<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
				<input id="txtUsername" name="txtUsername" type="text" class="inputbox" size="15" maxlength="20" /> Username<br />
				<input id="pwdPassword" name="pwdPassword" type="password" class="inputbox" size="15" /> Password<br />
				<label for="remember" title="Remember me"><input id="remember" name="remember" type="checkbox" checked="checked" /> Remember me</label><br />
				<input type="submit" name="login" class="button" value="Login" />
				<?php
				if (true == $CFG_NEWUSERS && (0 == $CFG_MAXUSERS || $numPlayers < $CFG_MAXUSERS))
				{
					?><input name="newAccount" class="button" value="New Account" type="button" onClick="window.open('newuser.php', '_self')" /><?php
				}
				?>

			</form>
		</div>
		<div id="footerspacer">&nbsp;</div>
		<div id="footer">
			<p>WebChess 2.1.1, last updated July 7, 2014</p>
			<p><a href="http://sourceforge.net/projects/webchess/">WebChess</a> is Free Software released under the GNU General Public License (GPL).</p>
			<p>For a copy of this version, please <a href="https://github.com/benjamw/chess">fork me on github</a>.</p>
		</div>
	</div>
	<?php call($GLOBALS); ?>
</body>
</html>