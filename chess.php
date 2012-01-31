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

// check if loading game
if (isset($_POST['game_id']))
{
	$_SESSION['game_id'] = (int) $_POST['game_id'];
}

// make sure we have game id data
if (empty($_SESSION['game_id'])) {
	header('Location: index.php');
	exit;
}

if (isset($_SESSION['game_id']) || ! isset($_SESSION['white']))
{
	// get White's data
	$query = "
		SELECT p_id
			, p_username
			, p_email
		FROM ".T_PLAYER."
			, ".T_GAME."
		WHERE ".T_PLAYER.".p_id = ".T_GAME.".g_white_player_id
			AND ".T_GAME.".g_id = '{$_SESSION['game_id']}'
	";
	$_SESSION['white'] = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	// get Black's data
	$query = "
		SELECT p_id
			, p_username
			, p_email
		FROM ".T_PLAYER."
			, ".T_GAME."
		WHERE ".T_PLAYER.".p_id = ".T_GAME.".g_black_player_id
			AND ".T_GAME.".g_id = '{$_SESSION['game_id']}'
	";
	$_SESSION['black'] = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	// get players' color
	if ($_SESSION['white']['p_username'] == $_SESSION['username'])
	{
		$_SESSION['player'] = &$_SESSION['white'];
		$_SESSION['player']['p_color'] = 'white';
		$_SESSION['opponent'] = &$_SESSION['black'];
		$_SESSION['opponent']['p_color'] = 'black';
	}
	else
	{
		$_SESSION['player'] = &$_SESSION['black'];
		$_SESSION['player']['p_color'] = 'black';
		$_SESSION['opponent'] = &$_SESSION['white'];
		$_SESSION['opponent']['p_color'] = 'white';
	}

	// get id960 and position
	$query = "
		SELECT g_id960
		FROM ".T_GAME."
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$_SESSION['id960'] = $mysql->fetch_value($query, __LINE__, __FILE__);
}

$initpos = id960_to_pos($_SESSION['id960']);

$promoting = false; // init the promotion flag
$undoing   = false; // init the undo flag

// get FEN array (this should probably be in an include somewhere)
$i = 0;
$query = "
	SELECT h_fen
	FROM ".T_HISTORY."
	WHERE h_game_id = '{$_SESSION['game_id']}'
	ORDER BY h_time
";
$FENarray = $mysql->fetch_value_array($query, __LINE__, __FILE__);

$num_moves = count($FENarray) - 1; // remove one for initpos

loadGame( ); // sets up board using last entry in ".T_HISTORY." table (chessdb.inc.php)
FENtomoves( ); // creates movesArray from FENarray (chessutils.inc.php)

// find out if it's the current player's turn
$FENitems = explode(' ',$FENarray[$num_moves]);
$curTurn  = $colorArray[$FENitems[1]]; // convert w -> white, b -> black

$isPlayersTurn = ($curTurn == $_SESSION['player']['p_color']) ? true : false;

//*/


//******************************************************************************
//  save incoming information
//******************************************************************************

checkDatabase( ); // check the database data against the current FEN to make sure the game is ended properly (chessdb.inc.php)

processMessages( ); // processes the messages (undo, resign, etc) (chessdb.inc.php)

// are we undoing ?
if ($undoing && 0 < $num_moves)
{
	call("UNDO REQUEST");
	// just remove the last FEN entered into the history table
	$query = "
		SELECT MAX(h_time)
		FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
	";
	$max_time = $mysql->fetch_value($query, __LINE__, __FILE__);

	$query = "
		DELETE FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
			AND h_time = '{$max_time}'
		LIMIT 1
	";
	$mysql->query($query, __LINE__, __FILE__);

	if (!DEBUG) header("Location: ./chess.php");
}
// or saving the promotion
else if ( isset($_POST['promotion']) && '' != $_POST['promotion'] && false != $_POST['promoting'] )
{
	call("SAVING PROMOTION");
	savePromotion( ); // inserts promoted piece and saves to database (chessdb.inc.php)

	if (!DEBUG) header("Location: ./chess.php");
}
// or making a move
else if ( ( isset($_POST['fromRow']) && '' != $_POST['fromRow'] && '' != $_POST['fromCol'] && '' != $_POST['toRow'] && '' != $_POST['toCol'] ) || ( isset($_POST['castleMove']) && 'false' != $_POST['castleMove'] ) )
{
	call("MAKING A MOVE");
	call($_POST);
	call($_POST['fromRow']);

	/* ensure it's the current player moving                                 */
	/* NOTE: if not, this will currently ignore the command...               */
	/*       perhaps the status should be instead?                           */
	/*       (Could be confusing to player if they double-click or something */
	$is_valid = true;
	if ('white' == $curTurn) // white's move
	{
		call("WHITE");
		call($board[$_POST['fromRow']][$_POST['fromCol']]);

		// ensure that piece being moved isn't black (and is a piece)
		if (('black' == $pieceColor[$board[$_POST['fromRow']][$_POST['fromCol']]]) || ('0' == $board[$_POST['fromRow']][$_POST['fromCol']]))
			$is_valid = false; // if test passes, piece was black
	}
	else // black' move
	{
		call("BLACK");
		call($pieceColor[$board[$_POST['fromRow']][$_POST['fromCol']]]);

		// ensure that piece being moved isn't white (and is a piece)
		if (("white" == $pieceColor[$board[$_POST['fromRow']][$_POST['fromCol']]]) || ('0' == $board[$_POST['fromRow']][$_POST['fromCol']]))
			$is_valid = false; // if test passes, piece was white
	}

	if ($is_valid)
	{
		call("IS VALID");
		saveGame( ); // (chessdb.inc.php)

		// reload a fresh page to avoid errors
		// and to display the new database data
		if (!DEBUG) header("Location: ./chess.php");
	}
}
// or we need to select the promoting piece
else if ('P' == strtoupper($movesArray[$num_moves]['piece']) && ( ! isset($movesArray[$num_moves]['promo']) || null == $movesArray[$num_moves]['promo']))
{
	if($movesArray[$num_moves]['toRow'] == 7 || $movesArray[$num_moves]['toRow'] == 0)
	{
		$promoting = true;
	}
}

//*/


//******************************************************************************
//  submit chat message
//******************************************************************************
if (isset($_POST['txtChatbox']) && ('' != $_POST['txtChatbox']))
{
	$_POST = sani($_POST);

	$private = (isset($_POST['private']) && 'on' == $_POST['private']) ? 'Yes' : 'No';

	// select the last post entered and make sure it is not a IE error duplicate message
	// (same message within 1 second)
	$query = "
		SELECT COUNT(*)
		FROM ".T_CHAT."
		WHERE c_message = '{$_POST['txtChatbox']}'
			AND c_time BETWEEN
				DATE_SUB(NOW( ), INTERVAL 1 SECOND)
				AND DATE_ADD(NOW( ), INTERVAL 1 SECOND)
	";
	$count = $mysql->fetch_value($query, __LINE__, __FILE__);

	if (0 == $count)
	{
		$query = "
			INSERT INTO ".T_CHAT."
				(c_game_id, c_player_id, c_time, c_message, c_private)
			VALUES
				('{$_SESSION['game_id']}', '{$_SESSION['player_id']}', NOW( ), '{$_POST['txtChatbox']}', '{$private}')
		";
		$mysql->query($query, __LINE__, __FILE__);
	}

	// refresh the page to avoid double posts
	if (!DEBUG) header('Location: chess.php');
}
//*/


//******************************************************************************
//  send wake up email
//******************************************************************************
$wake_up_sent = false;
if ( isset($_POST['wakeID']) && $_SESSION['game_id'] == $_POST['wakeID'] )
{
	call("webchessMail('wakeup',{$_SESSION['opponent']['p_email']},'',{$_SESSION['username']},{$_SESSION['game_id']})");
	$wake_up_sent = webchessMail('wakeup',$_SESSION['opponent']['p_email'],'',$_SESSION['username'],$_SESSION['game_id']);
}
//*/


//******************************************************************************
//  load game from database for display
//******************************************************************************

// get FEN array
$query = "
	SELECT h_fen
	FROM ".T_HISTORY."
	WHERE h_game_id = '{$_SESSION['game_id']}'
	ORDER BY h_time
";
$FENarray = $mysql->fetch_value_array($query, __LINE__, __FILE__);

$num_moves = count($FENarray) - 1; // remove one for initpos

loadGame( ); // sets up board using last entry in ".T_HISTORY." table (chessdb.inc.php)

// convert the current FEN array to an array of standard moves
FENtomoves( ); // (chessutils.inc.php)


// find out if it's the current player's turn
$FENitems = explode(' ',$FENarray[$num_moves]);
$curTurn  = $colorArray[$FENitems[1]];

$isPlayersTurn = ($curTurn == $_SESSION['player']['p_color']) ? true : false;

//*/

// set the display to show whos turn, or shared
if ($_SESSION['shared'])
{
	$turn = "Shared";
}
else if ($isPlayersTurn)
{
	$turn = "Your Move";
}
else
{
	$turn = "Opponent's Move";
}


$head_extra = '
	<script type="text/javascript">//<![CDATA[
		var watchgame = false;
		function redo( )
		{
			window.location.replace(\'chess.php\');
		}
		';

		// ouput confirmation for wake up email
		if ($wake_up_sent)
		{
			$head_extra .= "alert('Wake Up email sent');\n    ";
		}
		elseif (isset($_POST['wakeID']) && ! $wake_up_sent)
		{
			$head_extra .= "alert('Wake Up email FAILED !!');\n    ";
		}

		// transfer game data to javacript vars
		$head_extra .= getJSFEN( );  // writes 'FEN' array, and 'result' (gui.inc.php)
		$head_extra .= getTurn( );   // writes 'isBoardDisabled', 'isPlayersTurn', and 'perspective' (gui.inc.php)
		$head_extra .= getMoves( );  // writes the 'moves' array (gui.inc.php)
		$head_extra .= getStatus( ); // writes 'whosMove', 'gameState', and 'statusMsg' (gui.inc.php)

		$head_extra .= "var DEBUG = ".JS_DEBUG.";\n    ";
		$head_extra .= "var numMoves = FEN.length - 1;\n    ";

		// if it's not the player's turn, enable auto-refresh
		$autoRefresh = ( ! $isPlayersTurn && ! isBoardDisabled( ) && ! $_SESSION['shared'] );
		$head_extra .= "var autoreload = ";

		if ( ! $autoRefresh || (0 == $CFG_MINAUTORELOAD) )
		{
			$head_extra .= "0";
		}
		else if ( $_SESSION['pref_auto_reload'] >= $CFG_MINAUTORELOAD )
		{
			$head_extra .= $_SESSION['pref_auto_reload'];
		}
		else
		{
			$head_extra .= $CFG_MINAUTORELOAD;
		}

		$head_extra .= ";
			var gameId = '{$_SESSION['game_id']}';
			var players = '{$_SESSION['white']['p_username']} - {$_SESSION['black']['p_username']}';
			var promoting = '{$promoting}';
			var isGameOver = '{$isGameOver}';
			var lastMoveIndicator = '{$_SESSION['pref_show_last_move']}';
			var id960 = '{$_SESSION['id960']}';
			var initpos = '{$initpos}';
		";

		$head_extra .= "var currentTheme = '";
		$head_extra .= (isset($_SESSION['pref_theme']) ? $_SESSION['pref_theme'] : "plain") . '\';

			//]]>
		</script>
		<!-- the \'variables\' javascript must come first !! -->
		<script type="text/javascript" src="javascript/variables.js"></script>
		<script type="text/javascript" src="javascript/chessutils.js"></script>
		<script type="text/javascript" src="javascript/commands.js"></script>
		<script type="text/javascript" src="javascript/validation.js"></script>
	';

	if ($isPlayersTurn || $_SESSION['shared'] || $promoting)
	{
		$head_extra .= "\n	<script type=\"text/javascript\" src=\"javascript/isCheckMate.js\"></script>";
	}

	if ( ! isBoardDisabled( ) || $_SESSION['shared'])
	{
		$head_extra .= "\n	<script type=\"text/javascript\" src=\"javascript/squareclicked.js\"></script>";
	}

	$head_extra .= '<script type="text/javascript" src="javascript/board.js"></script>
		<script type="text/javascript" src="javascript/highlight.js"></script>
	';

	echo get_header(null, $turn, $head_extra)
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
			<form name="gamedata" method="post" action="chess.php">
				<?php
					if ($promoting && ( ! $isPlayersTurn || $_SESSION['shared'])) // Write promotion dialog only to the correct player
					{
						echo getPromotion( );
					}

					if ($isUndoRequested)
					{
						echo getUndoRequest( );
					}

					if ($isDrawRequested)
					{
						echo getDrawRequest( );
					}
				?>
				<div id="chessboard"></div>
				<div id="gamebuttons">
					<span id="castle">To castle: click the king, then the rook on the castle side.
					<a href="#" class="help" onclick="window.open('./help/c960castling.html','help','resizable,scrollbars,width=550,height=500,top=50,left=50','_blank');return false;">?</a></span>
					<div>
						<span id="curmove">&nbsp;</span>
						<input type="button" id="btnUndo" class="button" value="Request Undo" disabled="disabled" />
						<input type="button" id="btnDraw" class="button" value="Request Draw" disabled="disabled" />
						<input type="button" id="btnResign" class="button" value="Resign" disabled="disabled" />
					</div>
				</div>
				<input type="hidden" name="requestUndo" value="no" />
				<input type="hidden" name="requestDraw" value="no" />
				<input type="hidden" name="resign" value="no" />
				<input type="hidden" name="fromRow" value="" />
				<input type="hidden" name="fromCol" value="" />
				<input type="hidden" name="toRow" value="" />
				<input type="hidden" name="toCol" value="<?php if ($promoting) echo $movesArray[$num_moves]['toCol']; ?>" />
				<input type="hidden" name="castleMove" value="false" />
				<input type="hidden" name="promoting" value="<?php echo ($promoting ? 'true' : 'false'); ?>" />
			</form>
			<div id="gamenav"></div>
			<form name="gamemenu" id="gamemenu" method="post" action="chess.php" style="display:inline;">
				<input type="button" id="btnMainMenu" value="Menu" disabled="disabled" />
				<input type="button" id="btnReload" value="Reload" disabled="disabled" />
				<input type="button" id="btnReplay" value="Replay" disabled="disabled" />
				<input type="button" id="btnPGN" value="PGN" disabled="disabled" />
			</form>
			<form name="wakeup" id="wakeup" method="post" action="chess.php" style="display:inline;">
				<?php
					// test for opponents email, and if none, disable the wake up button
					$temp = ('' == $_SESSION['opponent']['p_email']) ? ' disabled="disabled"' : ''; // check the var and disable if no email is found
				?>
				<input type="button" id="btnWakeUp" value="Wake Up" onclick="wakeUp( );"<?php echo $temp; ?> /> <a href="#" class="help" onclick="window.open('./help/wakeup.html','help','resizable,scrollbars,width=550,height=500,top=50,left=50','_blank');return false;">?</a>
				<input type="hidden" name="wakeID" value="<?php echo $_SESSION['game_id']; ?>" />
			</form>
			<div id="captheading">Captured pieces</div>
			<div id="captures"></div>
		</div>
		<div id="chat">
			<h2>chat</h2>
			<div class="info">Newest messages on top</div>
			<div id="chatholder">
			<table class="chat">
				<col />
				<col class="message" />
				<tr>
					<th>Player</th>
					<th>Message</th>
				</tr><?php
				// collect the public chat messages
				$query = "
					SELECT c_message
						, c_private
						, p_username
					FROM ".T_CHAT."
						LEFT JOIN ".T_PLAYER."
							ON ".T_CHAT.".c_player_id = ".T_PLAYER.".p_id
					WHERE c_game_id = '{$_SESSION['game_id']}'
						AND (
							(c_private = 'No')
					";

				// include private message data if game is not shared
				if ('1' != $_SESSION['shared'])
				{
					$query .= "
							OR (c_private='Yes'
								AND ".T_CHAT.".c_player_id = '{$_SESSION['player_id']}')
					";
				}

				$query .= "
						)
					ORDER BY c_time DESC
				";
				$result = $mysql->fetch_array($query, __LINE__, __FILE__);

				$i = 0;
				foreach ($result as $chat)
				{
					$alt = ' class="';
					$alt .= (0 == $i % 2) ? ' alt' : '';
					$alt .= ('Yes' == $chat['c_private']) ? ' mine' : '';
					$alt .= ( $_SESSION['username'] != $chat['p_username'] ) ? ' opp' : '';
					$alt .= '"';

					echo "
				<tr{$alt}>
					<td class=\"player\">{$chat['p_username']}:</td>
					<td>{$chat['c_message']}</td>
				</tr>";
					$i++;
				}
				?>

			</table>
			</div>
			<form action="chess.php" method="post" name="chatdata" style="display:inline;">
				<input type="text" name="txtChatbox" tabindex="2" onfocus="clearTimeout(intervalId);" onblur="if(''==this.value){intervalId = setTimeout('redo( )', autoreload * 1000);}" /><br />
				<label for="private"><input type="checkbox" id="private" name="private" tabindex="1" />Private</label> <a href="#" class="help" onclick="window.open('./help/private.html','help','resizable,scrollbars,width=550,height=500,top=50,left=50','_blank');return false;">?</a>
				<input type="submit" id="btnSubmit" name="chat" tabindex="3" value="Submit" />
			</form>
		</div>
	</div>
	<div id="footerspacer">&nbsp;</div>
	<div id="FENblock"></div>
<?php call($GLOBALS); ?>
</body>
</html>
