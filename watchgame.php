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

// load 'always needed' settings
require_once './includes/config.inc.php';
require_once './includes/html.inc.php';

// include outside functions
require_once './includes/chessconstants.inc.php';
require_once './includes/chessutils.inc.php';
require_once './includes/gui.inc.php';
require_once './includes/chessdb.inc.php';


//******************************************************************************
//  load basic information
//******************************************************************************

$pgn = 0;
// check if we are loading a PGN file
if (isset($_GET['file']))
{
	if ('upload' == $_GET['file'])
	{
		// do upload stuff
	}
	else
	{
		$pgnReadFile = $_GET['file'];
		$pgn = 1;
		require './includes/readpgn.inc.php';
		$players = "{$white} vs {$black}";
		$_SESSION['id960'] = $id960;
		$_SESSION['game_id'] = 0;
	}
}
else // we are not loading a PGN file
{
	// check if loading game
	if ( isset($_REQUEST['game_id']) )
	{
		$_SESSION['game_id'] = $_REQUEST['game_id'];
	}

	// get FEN array
	$i = 0;
	$query = "
		SELECT h_fen
		FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
		ORDER BY h_time
	";
	$FENarray = $mysql->fetch_value_array($query, __LINE__, __FILE__);

	// convert the current FEN array to an array of standard moves
	$num_moves = count($FENarray) - 1; // remove one for initpos
	FENtomoves( ); // (chessutils.inc.php)

	// get White's username
	$query = "
		SELECT p_username
		FROM ".T_PLAYER."
			, ".T_GAME."
		WHERE ".T_PLAYER.".p_id = ".T_GAME.".g_white_player_id
			AND ".T_GAME.".g_id = '{$_SESSION['game_id']}'
	";
	$white_username = $mysql->fetch_value($query, __LINE__, __FILE__);

	// get Black's username
	$query = "
		SELECT p_username
		FROM ".T_PLAYER."
			, ".T_GAME."
		WHERE ".T_PLAYER.".p_id = ".T_GAME.".g_black_player_id
			AND ".T_GAME.".g_id = '{$_SESSION['game_id']}'
	";
	$black_username = $mysql->fetch_value($query, __LINE__, __FILE__);

	// get id960 and position
	$query = "
		SELECT g_id960
		FROM ".T_GAME."
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$id960 = $mysql->fetch_value($query, __LINE__, __FILE__);

	$_SESSION['id960'] = $id960;
	$initpos = id960_to_pos($id960);

	// load players
	$players = "{$white_username} vs {$black_username}";
}

// create a default player color
$_SESSION['player']['p_color'] = 'white';

// convert the current FEN array to an array of standard moves
FENtomoves( ); // (chessutils.inc.php)
//*/


$head_extra = '
	<script type="text/javascript">//<![CDATA[
		var watchgame = true;
		function redo( )
		{
			window.location.replace("watchgame.php");
		}
		';

	// transfer board data to javacript
	$head_extra .= getJSFEN( ); // writes 'FEN' array, and 'result' (gui.inc.php)
	$head_extra .= getTurn( );  // writes 'isBoardDisabled', 'isPlayersTurn', and 'perspective' (gui.inc.php)
	$head_extra .= getMoves( ); // writes the 'moves' array (gui.inc.php)

	$head_extra .= "var DEBUG = ".JS_DEBUG.";
		var autoreload = '0';
		var gameId = {$_SESSION['game_id']};
		var players = '{$players}';
		var id960 = '{$_SESSION['id960']}';
		var initpos = '{$initpos}';
		var isGameOver = 'false';
	";

	$head_extra .= "var currentTheme = '";
	$head_extra .= (isset($_SESSION['pref_theme']) ? $_SESSION['pref_theme'] : "plain") . '\';

		//]]>
	</script>
	<!-- the variables javascript must come first !! -->
	<script type="text/javascript" src="./javascript/variables.js"></script>
	<script type="text/javascript" src="./javascript/chessutils.js"></script>
	<script type="text/javascript" src="./javascript/commands.js"></script>
	<script type="text/javascript" src="./javascript/board.js"></script>
	<script type="text/javascript" src="./javascript/highlight.js"></script>
	';

	echo get_header(null, 'Watch Game', $head_extra)
?>

		<div id="history">
			<h2 id="players"></h2>
			<h3 id="gameid"></h3>
			<div id="gamebody"></div>
		</div>
		<div id="board">
			<div id="checkmsg"></div>
			<div id="statusmsg"></div>
			<div id="date"><?php echo date($CFG_LONGDATE); ?></div>
			<div id="chessboard"></div>
			<div id="gamenav"></div>
			<form name="gamemenu" id="gamemenu" method="post" action="chess.php" style="display:inline;">
				<input type="button" id="btnMainMenu" value="Menu" disabled="disabled" />
				<input type="button" id="btnReload" value="Reload" disabled="disabled" />
				<input type="button" id="btnReplay" value="Replay" disabled="disabled" />
				<input type="button" id="btnPGN" value="PGN" disabled="disabled" />
			</form>
			<div id="captheading">Captured pieces</div>
			<div id="captures"></div>
		</div>
		<div id="chat"></div>
	</div>
	<div id="footerspacer">&nbsp;</div>
	<div id="FENblock"></div>
<?php call($GLOBALS); ?>
</body>
</html>
