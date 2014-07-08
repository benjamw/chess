<?php

// note to developers :
// before each section, there is a header that begins with somthing like

	//**********************************************************************
	//  this is the header
	//**********************************************************************

// and after the respective section there is an entity like

	//*/

// together these entities create a switchable comment block.
// when used together, all you need to do is remove one of the forward slashes ( / )
// from either of the lines containing asterisks ( * ) to make a line like

	/***********************************************************************

	... everything in here is commented out with one easy character change ...

	//*/

// by finding a commented block of code, all you need to do is add the missing /
// to the line that is missing one. ( i usually remove the slash from the lower of
// the two lines )
//
// i specifically use various comment styles to denote what kind of comment it is.
// for instance, i use // to denote a 'real' comment, where i am commenting on a
// piece of code, i use /* */ to comment out blocks of code that i don't wish to run
// and i use # to temporarily comment out single lines or small sections of code.
//
// ... although there may be other comments elsewhere that other people created that
// do not follow this protocol


// set the session cookie parameters so the cookie is only valid for this game
$parts = pathinfo($_SERVER['REQUEST_URI']);

$path = $parts['dirname'];
if (empty($parts['extension'])) {
	$path .= $parts['basename'];
}
$path = str_replace('\\', '/', $path).'/';

session_set_cookie_params(0, $path);
@session_start( );

// load settings
require_once 'config.inc.php';
require 'login.inc.php';
require_once 'html.inc.php';
require_once 'html.tables.php';

// reset any shared data we may have collected
$_SESSION['shared'] = false;

// run some things first for the menu and data below
// open the PGN dir
$pgnDir = opendir("./pgn");
$fullFiles = array( );

// collect the game IDs for the games already exported
while (false !== ($file = readdir($pgnDir)))
{
	if (('..' != $file) && ('.' != $file) && ('index.php' != $file))
	{
		// collect the complete filename
		$fullFiles[] = $file;

		// collect just the game id for searching below
		if (preg_match('/game_(\\d++)/i', $file, $match))
		{
			$pgnFiles[] = $match[1];
		}
	}
}

// count the files for menu display
$numFiles = count($fullFiles);

// run a switch so that all the stuff being run here is not run all at once
// when viewing the page, it makes it so that the index page takes FOREVER
// to load

// make sure we have something to look at
$page = (isset($_GET['page'])) ? $_GET['page'] : 'mygames';
switch ($page)
{
// =========================================================================
	case 'mygames' :

		break;

// =========================================================================
	case 'current' :

		//******************************************************************
		//  cleanup dead games
		//******************************************************************

		// if we want to delete old games
		if (0 != $CFG_EXPIREGAME)
		{
			// find out which games are older
			$query = "
				SELECT *
				FROM ".T_GAME."
				WHERE g_last_move < DATE_SUB(NOW( ), INTERVAL {$CFG_EXPIREGAME} DAY)
			";
			$result = $mysql->fetch_array($query, __LINE__, __FILE__);

			// and for every old game...
			foreach ($result as $old_game)
			{
				// if the game is in progress...
				if ('' == $old_game['g_game_message'])
				{
					// find out who's turn it was...
					$query = "
						SELECT COUNT(*)
						FROM ".T_HISTORY."
						WHERE h_game_id = '{$old_game['g_id']}'
					";
					$old_moves = $mysql->fetch_value($query, __LINE__, __FILE__);

					// if it's black's turn...
					if (0 == ($old_moves % 2))
					{
						$player1 = $old_game['g_white_player_id']; // give white a win
						$player2 = $old_game['g_black_player_id']; // give black a loss
					}
					else // it was white's turn...
					{
						$player1 = $old_game['g_black_player_id']; // give black a win
						$player2 = $old_game['g_white_player_id']; // give white a loss
					}

					// require a file
					require_once './includes/chessdb.inc.php';

					adjust_stats($player1, $player2, 1, 0);
				}

				// ...clear the history...
				$query = "
					DELETE FROM ".T_HISTORY."
					WHERE h_game_id = '{$old_game['g_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);

				// ...and the messages...
				$query = "
					DELETE FROM ".T_MESSAGE."
					WHERE m_game_id = '{$old_game['g_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);

				// ...and the chat...
				$query = "
					DELETE FROM ".T_CHAT."
					WHERE c_game_id = '{$old_game['g_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);

				// ...and finally the game itself from the database
				$query = "
					DELETE FROM ".T_GAME."
					WHERE g_id = '{$old_game['g_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);
			}
		}
		//*/

		break;

// =========================================================================
	case 'finished' :
		require_once 'chessutils.inc.php';
		require_once 'gui.inc.php';

		//******************************************************************
		//  Export finished games to PGN directory
		//******************************************************************

		// save the current session vars
		if (isset($_SESSION['game_id']))
		{
			$prevSessionGameID = $_SESSION['game_id'];
		}

		if (isset($_SESSION['id960']))
		{
			$prevSessionId960 = $_SESSION['id960'];
		}

		// save the list to exclude them below
		$pgnFiles = isset($pgnFiles) ? implode(',', $pgnFiles) : '0';

		// get all the games that are finished, but not yet exported to PGN
		$query = "
			SELECT G.*
				, P1.p_username AS white_username
				, P2.p_username AS black_username
			FROM ".T_GAME." AS G
				LEFT JOIN ".T_PLAYER." AS P1
					ON P1.p_id = G.g_white_player_id
				LEFT JOIN ".T_PLAYER." AS P2
					ON P2.p_id = G.g_black_player_id
			WHERE G.g_id NOT IN ({$pgnFiles})
				AND G.g_game_message IN ('Draw','Player Resigned','Checkmate')
			ORDER BY G.g_id
		";
		$result = $mysql->fetch_array($query, __LINE__, __FILE__);

		// save those games to PGN
		foreach ($result as $game)
		{
			$_SESSION['game_id'] = $game['g_id'];
			$_SESSION['id960']   = $game['g_id960'];

			ob_start( );

			// get the FEN data from the database
			$i = 0;
			$query = "
				SELECT h_fen
				FROM ".T_HISTORY."
				WHERE h_game_id = '{$_SESSION['game_id']}'
				ORDER BY h_time
			";
			$result = $mysql->fetch_array($query, __LINE__, __FILE__);

			unset($FENarray);
			foreach ($result as $FEN)
			{
				$FENarray[$i] = $FEN[0];
				$i++;
			}

			FENtomoves( ); // (chessutils.inc.php)

			returnGameInfo($_SESSION['game_id']);
			echo getPGN( ); // (gui.inc.php)

			$pgnFile = "./pgn/WebChess2_Game_{$game['g_id']}_".str_replace('-', '', substr($game['g_last_move'], 0, 10 )).'.pgn';

			$fh = fopen($pgnFile, "w")
				or die("Couldn't open the file {$pgnFile}. Please check file permissions\n");
			fwrite($fh, ob_get_contents( ))
				or die("Couldn't write to the file $pgnFile. Please check file permissions\n");
			fclose($fh);
			ob_end_clean( );
		}

		// reinstate the saved session vars
		if (isset($prevSessionGameID))
		{
			$_SESSION['game_id'] = $prevSessionGameID;
		}

		if (isset($prevSessionId960))
		{
			$_SESSION['id960'] = $prevSessionId960;
		}
		//*/


		//******************************************************************
		//  send email notice for nearly dead games
		/******************************************************************

		// get 2/3 of expire length
		$targetDate = mktime(0,0,0, date('m'), date('d') - ($CFG_EXPIREGAME * (2/3)), date('Y'));

		$query = "
			SELECT *
			FROM ".T_GAME."
			WHERE g_last_move < '{$targetDate}'
		";
		$result = $mysql->fetch_array($query, __LINE__, __FILE__);

		foreach ($result as $game)
		{
			$query = "
				SELECT p_email
				FROM ".T_PLAYER."
				WHERE p_id = '{$game['g_white_player_id']}'
			";
			$whiteEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

			$query = "
				SELECT p_email
				FROM ".T_PLAYER."
				WHERE p_id = '{$game['g_black_player_id']}'
			";
			$blackEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

			webchessMail("deletewarning",$whiteEmail,0,0,$game['g_id']);
			webchessMail("deletewarning",$blackEmail,0,0,$game['g_id']);
		}
		//*/

		break;

// =========================================================================
	case 'invite' :
		require_once 'newgame.inc.php';
		require_once 'webchessmail.inc.php';

		//******************************************************************
		//  send invitation for a new game
		//******************************************************************

		if (isset($_POST['opponent']))
		{
			// prevent multiple invites from the same originator
			$P = sani($_POST);
			$query = "
				SELECT g_id
				FROM ".T_GAME."
				WHERE g_game_message = 'Player Invited'
					AND
					(
						(
							g_message_from = 'white'
							AND g_white_player_id = '{$_SESSION['player_id']}'
							AND g_black_player_id = '{$P['opponent']}'
						)
						OR
						(
							g_message_from = 'black'
							AND g_white_player_id = '{$P['opponent']}'
							AND g_black_player_id = '{$_SESSION['player_id']}'
						)
					)
			";
			$mysql->query($query, __LINE__, __FILE__);

			if (0 == $mysql->num_rows( ))
			{
				// set the color to a random color if 'random', otherwise, set the color given
				$color = ('random' == $P['color']) ? ((1 == mt_rand(0, 1)) ? 'white' : 'black') : $P['color'];

				$query = "
					INSERT INTO ".T_GAME."
						(g_id960, g_white_player_id, g_black_player_id, g_game_message, g_message_from, g_date_created, g_last_move)
					VALUES ('";

				// set the C960 ID
				if ( ! isset($P['txtId960']) || '' === $P['txtId960'] )
				{
					$query .= "518','";
				}
				else
				{
					$query .= (int) trim($P['txtId960']) . "','";
				}

				// put the players in the right order
				if ('white' == $color)
				{
					$query .= $_SESSION['player_id'] . "','" . $P['opponent'];
				}
				else
				{
					$query .= $P['opponent'] . "','" . $_SESSION['player_id'];
				}

				$query .= "','Player Invited','{$color}',NOW( ),NOW( )) ";
				$mysql->query($query, __LINE__, __FILE__);

				// if email notification is activated...
				if ($CFG_USEEMAIL)
				{
					// if opponent is using email notification...
					$query = "
						SELECT p_email
						FROM ".T_PLAYER."
						WHERE p_id = '{$_POST['opponent']}'
					";
					$opponentEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

					if (0 < $mysql->num_rows( ))
					{
						if ('' != $opponentEmail)
						{
							// notify opponent of invitation via email
							call("webchessMail('invitation',$opponentEmail,'',{$_SESSION['username']},'')");
							webchessMail('invitation',$opponentEmail,'',$_SESSION['username'],'');
						}
					}
				}
			}
		}
		//*/


		//******************************************************************
		//  respond to invitation
		//******************************************************************

		if (isset($_POST['respond']))
		{
			if ('accepted' == $_POST['respond'])
			{
				// update game data
				$query = "
					UPDATE ".T_GAME."
					SET g_game_message = ''
						, g_message_from = ''
					WHERE g_id = '{$_POST['game_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);

				// get the opponents ID for email
				$query = "
					SELECT *
					FROM ".T_GAME."
					WHERE g_id = '{$_POST['game_id']}'
				";
				$game = $mysql->fetch_assoc($query, __LINE__, __FILE__);

				$oppID = ($game['g_white_player_id'] == $_SESSION['player_id']) ? $game['g_black_player_id'] : $game['g_white_player_id'];

				// setup new board
				$_SESSION['game_id'] = $_POST['game_id'];
				createNewGame($_POST['game_id'],$game['g_id960']); // (newgame.inc.php)
#				saveGame( ); // (chessdb.inc.php)

				// if email notification is activated...
				if ($CFG_USEEMAIL)
				{
					// if opponent is using email notification...
					$query = "
						SELECT p_email
						FROM ".T_PLAYER."
						WHERE p_id = '{$oppID}'
					";
					$opponentEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

					if (0 < $mysql->num_rows( ))
					{
						if ('' != $opponentEmail)
						{
							// notify opponent of invitation via email
							call("webchessMail('accepted',$opponentEmail,'',{$_SESSION['username']},'')");
							webchessMail('accepted',$opponentEmail,'',$_SESSION['username'],'');
						}
					}
				}
			}
			else
			{
				$query = "
					UPDATE ".T_GAME."
					SET g_game_message = 'Invite Declined'
						, g_message_from = '{$_POST['message_from']}'
					WHERE g_id = '{$_POST['game_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);

				// get the opponents ID for email
				$query = "
					SELECT *
					FROM ".T_GAME."
					WHERE g_id = '{$_POST['game_id']}'
				";
				$game = $mysql->fetch_assoc($query, __LINE__, __FILE__);
								$oppID = ($game['g_white_player_id'] == $_SESSION['player_id']) ? $game['g_black_player_id'] : $game['g_white_player_id'];

				// if email notification is activated...
				if ($CFG_USEEMAIL)
				{
					// if opponent is using email notification...
					$query = "
						SELECT p_email
						FROM ".T_PLAYER."
						WHERE p_id = '{$oppID}'
					";
					$opponentEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

					if (0 < $mysql->num_rows( ))
					{
						if ('' != $opponentEmail)
						{
							// notify opponent of decline via email
							call("webchessMail('declined',$opponentEmail,'',{$_SESSION['username']},'')");
							webchessMail('declined',$opponentEmail,'',$_SESSION['username'],'');
						}
					}
				}
			}
		}
		//*/


		//******************************************************************
		//  withdraw invitation
		//******************************************************************

		if (isset($_POST['withdrawl']))
		{
			// get opponent's player ID
			$query = "
				SELECT g_white_player_id
				FROM ".T_GAME."
				WHERE g_id = '{$_POST['game_id']}'
			";
			$opponentID = $mysql->fetch_value($query, __LINE__, __FILE__);

			if (0 < $mysql->num_rows( ))
			{
				if ($opponentID == $_SESSION['player_id'])
				{
					$query = "
						SELECT g_black_player_id
						FROM ".T_GAME."
						WHERE g_id = '{$_POST['game_id']}'
					";
					$opponentID = $mysql->fetch_value($query, __LINE__, __FILE__);
				}

				$query = "
					DELETE FROM ".T_GAME."
					WHERE g_id = '{$_POST['game_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);

				// if email notification is activated...
				if ($CFG_USEEMAIL)
				{
					// if opponent is using email notification...
					$query = "
						SELECT p_email
						FROM ".T_PLAYER."
						WHERE p_id = '{$opponentID}'
					";
					$opponentEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

					if (0 < $mysql->num_rows( ))
					{
						if ($opponentEmail != '')
						{
							// notify opponent of invitation via email
							call("webchessMail('withdrawal', $opponentEmail, '', {$_SESSION['username']}, {$_POST['game_id']})");
							webchessMail('withdrawal', $opponentEmail, '', $_SESSION['username'], $_POST['game_id']);
						}
					}
				}
			}
		}
		//*/

		break;

// =========================================================================
	case 'stats' :

		//******************************************************************
		//  run the games data for the stats
		//******************************************************************

		// collect current stats from database
		$query = "
			SELECT s_id
				, s_moves
				, s_days
			FROM ".T_STAT."
			WHERE
				(
					s_moves IS NOT NULL
					AND s_moves != 0
				)
				OR
				(
					s_days IS NOT NULL
					AND s_days != 0
				)
		";
		$statsdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		// parse stats data into an array for easier searching
		foreach ($statsdata as $stat)
		{
			$curstats[$stat['s_id']]['moves'] = $stat['s_moves'];
			$curstats[$stat['s_id']]['days']  = $stat['s_days'];
		}

		// collect any finished game data from the database
		$query = "
			SELECT COUNT(h_game_id) AS moves
				, DATEDIFF(g_last_move, g_date_created) AS days
				, h_game_id AS id
			FROM ".T_HISTORY."
				, ".T_GAME."
			WHERE ".T_HISTORY.".h_game_id = ".T_GAME.".g_id
				AND g_game_message IN ('Draw', 'Player Resigned', 'Checkmate')
			GROUP BY h_game_id
		";
		$gamedata = $mysql->fetch_array($query, __LINE__, __FILE__);

		// parse through each, compare with current, and add stats as needed
		foreach ($gamedata as $game)
		{
			// clear any previous query
			$query = '';

			if ( ! isset($curstats[$game['id']]))
			{
				$query = "
					INSERT INTO ".T_STAT."
						(s_id, s_days, s_moves)
					VALUES
						('{$game['id']}', '{$game['days']}', '{$game['moves']}')
				";
			}
			elseif ($curstats[$game['id']]['moves'] != $game['moves'] || $curstats[$game['id']]['days'] != $game['days'])
			{
				$query = "
					UPDATE ".T_STAT."
					SET s_moves = '{$game['moves']}'
						, s_days  = '{$game['days']}'
					WHERE s_id = '{$game['id']}'
				";
			}

			// run the query
			if ('' != $query)
			{
				$mysql->query($query, __LINE__, __FILE__);
			}
		}
		//*/

		// stats -----------------------------------------------------------

		$query = "
			SELECT s_id
				, s_days
			FROM ".T_STAT."
			WHERE s_days IS NOT NULL
			ORDER BY s_days DESC
			LIMIT 0,5
		";
		$daysLdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		$query = "
			SELECT s_id
				, s_days
			FROM ".T_STAT."
			WHERE s_days IS NOT NULL
			ORDER BY s_days ASC
			LIMIT 0,5
		";
		$daysSdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		$query = "
			SELECT s_id
				, s_moves
			FROM ".T_STAT."
			WHERE s_moves IS NOT NULL
			ORDER BY s_moves DESC
			LIMIT 0,5
		";
		$movesLdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		$query = "
			SELECT s_id
				, s_moves
			FROM ".T_STAT."
			WHERE s_moves IS NOT NULL
			ORDER BY s_moves ASC
			LIMIT 0,5
		";
		$movesSdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		$query = "
			SELECT s_id
				, MAX(ABS(s_streak)) AS s_streak
				, p_username
			FROM ".T_STAT."
				LEFT JOIN ".T_PLAYER."
					ON p_id = s_id
			WHERE s_streak IS NOT NULL
			GROUP BY s_id
			ORDER BY s_streak DESC
			LIMIT 0,5
		";
		$streakdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		$query = "
			SELECT p_username
				, p_wins
				, p_draws
				, p_losses
				, p_rating
			FROM ".T_PLAYER."
		";
		$playerdata = $mysql->fetch_array($query, __LINE__, __FILE__);

		break;

// =========================================================================
	case 'messages' :

		if (isset($_POST['action'])) {
			try {
				switch ($_POST['action']) {
					case 'read' :
						$Message->set_message_read($_POST['ids']);
						break;

					case 'unread' :
						$Message->set_message_unread($_POST['ids']);
						break;

					case 'delete' :
						$Message->delete_message($_POST['ids']);
						break;

					default :
						break;
				}
			}
			catch (MyException $e) { }
		}

		break;

// =========================================================================
	case 'send' :

		if (isset($_POST['submit'])) {
			// clean the data
			$subject = $_POST['subject'];
			$message = $_POST['message'];
			$user_ids = (array) ife($_POST['user_ids'], array( ), false);
			$send_date = ife($_POST['send_date'], false, false);
			$expire_date = ife($_POST['expire_date'], false, false);

			try {
				$Message->send_message($subject, $message, $user_ids, $send_date, $expire_date);
			}
			catch (MyException $e) {
			}

			header('Location: index.php?page=messages');
			exit;
		}

		$message = array(
			'subject' => '',
			'message' => '',
		);

		if (isset($_GET['id'])) {
			try {
				if (isset($_GET['type']) && ('fw' == $_GET['type'])) { // forward
					$message = $Message->get_message_forward((int) $_GET['id']);
				}
				elseif (isset($_GET['type']) && ('rs' == $_GET['type'])) { // resend
					$message = $Message->get_message((int) $_GET['id']);
				}
				else { // reply
					$message = $Message->get_message_reply((int) $_GET['id']);
					$reply_flag = true;
				}
			}
			catch (MyException $e) { }
		}

		// grab a list of the players
		$query = "
			SELECT `p_id`
				, `p_username`
			FROM `".T_PLAYER."`
			ORDER BY `p_username`
		";
		$list = $mysql->fetch_array($query);

		$recipient_options = '';
		if (is_array($list)) {
			// send global messages if we can
			if ($_SESSION['is_admin']) {
				$recipient_options .= '<option value="0">GLOBAL</option>';
			}

			$recipient_id = (isset($message['recipients'][0]['from_id']) && ! empty($reply_flag)) ? $message['recipients'][0]['from_id'] : 0;

			foreach ($list as $player) {
				// remove ourselves from the list
				if ($player['p_id'] == $_SESSION['player_id']) {
					continue;
				}

				$recipient_options .= '<option value="'.$player['p_id'].'"'.get_selected($recipient_id, $player['p_id']).'>'.$player['p_username'].'</option>';
			}
		}

		break;

// =========================================================================
	case 'read' :

		if (isset($_POST['type']) && ('' != $_POST['type'])) {
			switch ($_POST['type']) {
				case 'delete' :
					$Message->delete_message((int) $_POST['message_id']);
					header('Location: index.php?page=messages');
					exit;
					break;

				default :
					break;
			}
		}

		if ( ! isset($_GET['id'])) {
			session_write_close( );
			header('Location: index.php?page=messages');
			exit;
		}

		try {
			$message = $Message->get_message($_GET['id'], $_SESSION['is_admin']);
			$message['message'] = str_replace("\t", ' &nbsp; &nbsp;', $message['message']);
			$message['message'] = str_replace('  ', ' &nbsp;', $message['message']);
			$message['message'] = htmlentities($message['message'], ENT_QUOTES, 'ISO-8859-1', false);
			$message['message'] = nl2br($message['message']);

			$message['subject'] = htmlentities($message['subject'], ENT_QUOTES, 'ISO-8859-1', false);

			// find out if we're reading an inbox message, or an outbox message
			if ($message['inbox']) {
				$list = $Message->get_outbox_list( );
			}
			elseif ($message['allowed']) {
				$list = $Message->get_inbox_list( );
			}
			else {
				$list = $Message->get_admin_list( );
			}
		}
		catch (MyException $e) { }

		// grab data for our prev | next links
		$prev = false;
		$next = false;
		$current = false;
		$prev_item = false;
		foreach ($list as $item) {
			if ($current) {
				$current = false;
				$next = $item['message_id'];
			}

			if ($item['message_id'] == $_GET['id']) {
				$current = true;
				$prev = $prev_item['message_id'];
			}

			$prev_item = $item;
		}

		break;

// =========================================================================
	case 'prefs' :

		//******************************************************************
		//  update your preferences
		//******************************************************************

		if (isset($_POST['selHistory']))
		{
			call("UPDATE PREFS");
			// set auto-reload preference
			$reload = (is_numeric($_POST['txtReload']) && ( intval($_POST['txtReload']) >= $CFG_MINAUTORELOAD) ) ? $_POST['txtReload'] : $CFG_MINAUTORELOAD;

			$lastMove = isset($_POST['boxLastMove']) ? 1 : 0;

			// Theme
			$query = "
				UPDATE ".T_PLAYER."
				SET p_theme         = '{$_POST['rdoTheme']}'
					, p_history     = '{$_POST['selHistory']}'
					, p_auto_reload = '".sani($reload)."'
					, p_max_games   = '".sani($_POST['txtmaxGames'])."'
					, p_show_last_move = '{$lastMove}'
				WHERE p_id = '{$_SESSION['player_id']}'
			";
			$mysql->query($query, __LINE__, __FILE__);

			// update current session vars with a page refresh
			header('Location: index.php?page=prefs');
			exit;
		}
		//*/

		break;

// =========================================================================
	case 'personal' :

		//******************************************************************
		//  update your personal information
		//******************************************************************

		if (isset($_POST['txtFirstName']))
		{
			$query = "
				SELECT p_password
				FROM ".T_PLAYER."
				WHERE p_id = '{$_SESSION['player_id']}'
			";
			$dbPassword = $mysql->fetch_value($query, __LINE__, __FILE__);

			if ((isset($_POST['pwdPassword']) && ('' != $_POST['pwdPassword'])) && ($dbPassword != substr($_POST['pwdOldPassword'],5)))
			{
				$errMsg = "Sorry, incorrect old password!";
			}
			else
			{
				$doUpdate = true;

				if ($CFG_CHANGEUSERNAME)
				{
					$query = "
						SELECT p_id
						FROM ".T_PLAYER."
						WHERE p_username = '".sani($_POST['txtUsername'])."'
							AND p_id != '{$_SESSION['player_id']}'
					";
					$mysql->query($query, __LINE__, __FILE__);

					if (0 < $mysql->num_rows( ))
					{
						$errMsg = "Sorry, that username is already in use.";
						$doUpdate = false;
					}
				}

				// if it's set, then it's allowed
				$email = isset($_POST['txtEmail']) ? $_POST['txtEmail'] : '';

				if ($doUpdate)
				{
					// update DB
					$query = "
						UPDATE ".T_PLAYER."
						SET p_first_name  = '".sani($_POST['txtFirstName'])."'
							, p_last_name = '".sani($_POST['txtLastName'])."'
				 			, p_email     = '".sani($email)."'
					"; // continued...

					if (isset($_POST['pwdPassword']) && ('' != $_POST['pwdPassword']))
					{
						$query .= " , p_password = '".substr($_POST['pwdPassword'],5)."' "; // continued...
					}

					if ((false != $CFG_CHANGEUSERNAME) && ('' != $_POST['txtUsername']))
					{
						$_SESSION['username'] = $_POST['txtUsername'];
						$query .= " , p_username = '".sani($_POST['txtUsername'])."' "; // continued...
					}

					$query .= " WHERE p_id = '{$_SESSION['player_id']}' ";
					$mysql->query($query, __LINE__, __FILE__);

					// update current session vars with a page refresh
					header('Location: index.php?page=personal');
					exit;
				}
			}
		}
		//*/


		//******************************************************************
		//  test your email address
		//******************************************************************

		if (isset($_POST['testmail']) && (false != $CFG_USEEMAIL))
		{
			webchessMail('test', $_SESSION['email'], '', '', '');
		}
		//*/

		break;

// =========================================================================
	case 'admin' :
		require_once 'webchessmail.inc.php';

		//******************************************************************
		//  run administration functions
		//******************************************************************

		if (isset($_POST))
		{
			// set all admin flags to 0...
			$query = "
				UPDATE ".T_PLAYER."
				SET p_is_admin = '0'
				WHERE 1
			";
			$mysql->query($query, __LINE__, __FILE__);

			// set the current user to admin
			// because if they accessed this, they are admin
			$query = "
				UPDATE ".T_PLAYER."
				SET p_is_admin = '1'
				WHERE p_id = '{$_SESSION['player_id']}'
				LIMIT 1
			";
			$mysql->query($query, __LINE__, __FILE__);

			// update admin before deleting
			if ( isset($_POST['admin']) )
			{
				foreach ( $_POST['admin'] as $user )
				{
					// ...then adminify all the checked ones
					$query = "
						UPDATE ".T_PLAYER."
						SET p_is_admin = '1'
						WHERE p_id = '{$user}'
						LIMIT 1
					";
					$mysql->query($query, __LINE__, __FILE__);
				}
			}

			// reset passwords before deleting as well
			if ( isset($_POST['resetpass']) )
			{
				foreach ( $_POST['resetpass'] as $user )
				{
					// reset the password to change!me
					$pass = password_make('change!me');
					$query = "
						UPDATE ".T_PLAYER."
						SET p_password = '{$pass}'
						WHERE p_id = '{$user}'
						LIMIT 1
					";
					$mysql->query($query, __LINE__, __FILE__);

					// get the users email address
					$query = "
						SELECT p_email
						FROM ".T_PLAYER."
						WHERE p_id = '{$user}'
					";
					$email = $mysql->fetch_value($query, __LINE__, __FILE__);

					// email the user and let them know their password has been changed
					call("webchessMail('passupdate',$email,'','','')");
					webchessMail('passupdate',$email,'','','');
				}
			}

			$i = 0;
			if ( isset($_POST['delete']) )
			{
				foreach ( $_POST['delete'] as $user )
				{
					$query = "
						SELECT p_username
						FROM ".T_PLAYER."
						WHERE p_id = '{$user}'
					";
					$name = $mysql->fetch_value($query, __LINE__, __FILE__);

					// protect the root admin, just in case
					if ($CFG_ROOT_ADMIN != $name)
					{
						// find all the games that user was playing
						$query = "
							SELECT g_id
							FROM ".T_GAME."
							WHERE g_black_player_id = '{$user}'
								OR g_white_player_id = '{$user}'
						";
						$list = $mysql->fetch_value_array($query, __LINE__, __FILE__);
						call('list');
						$list[] = 0;
						$games = implode(',', $list);

						// delete all database entries related to those games
						$query = "
							DELETE FROM ".T_CHAT."
							WHERE c_game_id IN ({$games})
						";
						$mysql->query($query, __LINE__, __FILE__);

						$query = "
							DELETE FROM ".T_HISTORY."
							WHERE h_game_id IN ({$games})
						";
						$mysql->query($query, __LINE__, __FILE__);

						$query = "
							DELETE FROM ".T_MESSAGE."
							WHERE m_game_id IN ({$games})
						";
						$mysql->query($query, __LINE__, __FILE__);

						$query = "
							DELETE FROM ".T_GAME."
							WHERE g_id IN ({$games})
						";
						$mysql->query($query, __LINE__, __FILE__);


						// delete the communications related to that player
						$query = "
							DELETE FROM ".T_TALK."
							WHERE t_from_player_id = '{$user}'
								OR t_to_player_id = '{$user}'
						";
						$mysql->query($query, __LINE__, __FILE__);

						// finally, delete the player
						$query = "
							DELETE FROM ".T_PLAYER."
							WHERE p_id = {$user}
							LIMIT 1
						";
						$mysql->query($query, __LINE__, __FILE__);
						$i++;
					}
				}
			}
			if ( isset($i) && $i )
			{
				$errMsg = "! {$i} users deleted !";
			}
		}
		//*/

		// admin -----------------------------------------------------------

		$query = "
			SELECT *
				, UNIX_TIMESTAMP(p_created) AS u_created
			FROM ".T_PLAYER."
			WHERE p_id != '{$_SESSION['player_id']}'
				AND p_username != '{$CFG_ROOT_ADMIN}'
			ORDER BY p_username
		";
		$admin = $mysql->fetch_array($query, __LINE__, __FILE__);

		break;

// ====================================================
	default :
		// send them to the login page, if they are logged in
		// it will send them back to the index with proper data
		header('Location: login.php');
		exit;
		break;
} // the big switch


//******************************************************************************
//  run the queries (needed outside for the menu)
//******************************************************************************

// active ------------------------------------------------------------------

$query = "
	SELECT G.*
		, UNIX_TIMESTAMP(G.g_date_created) AS u_date_created
		, UNIX_TIMESTAMP(G.g_last_move) AS u_last_move
		, COUNT(H.h_time) - 1 AS num_moves
		, P1.p_username AS white_username
		, P2.p_username AS black_username
	FROM ".T_GAME." AS G
		LEFT JOIN ".T_HISTORY." AS H
			ON H.h_game_id = G.g_id
		LEFT JOIN ".T_PLAYER." AS P1
			ON P1.p_id = G.g_white_player_id
		LEFT JOIN ".T_PLAYER." AS P2
			ON P2.p_id = G.g_black_player_id
	WHERE G.g_game_message = ''
		AND (
			G.g_white_player_id = '{$_SESSION['player_id']}'
			OR G.g_black_player_id = '{$_SESSION['player_id']}'
		)
	GROUP BY G.g_id
	ORDER BY G.g_date_created
";
$active = $mysql->fetch_array($query, __LINE__, __FILE__);
$numActive = $mysql->num_rows( );


// messages ----------------------------------------------------------------


list($numMsgs, $newMsgs) = Message::get_count($_SESSION['player_id']);


// invites -----------------------------------------------------------------

$query = "
	SELECT G.*
		, UNIX_TIMESTAMP(G.g_date_created) AS u_date_created
		, P1.p_username AS white_username
		, P2.p_username AS black_username
		, 'invite' AS invite
		, 0 AS num_moves
	FROM ".T_GAME." AS G
		LEFT JOIN ".T_PLAYER." AS P1
			ON P1.p_id = G.g_white_player_id
		LEFT JOIN ".T_PLAYER." AS P2
			ON P2.p_id = G.g_black_player_id
	WHERE G.g_game_message = 'Player Invited'
		AND (
			(
				G.g_white_player_id = '{$_SESSION['player_id']}'
				AND G.g_message_from = 'black'
			)
			OR (
				G.g_black_player_id = '{$_SESSION['player_id']}'
				AND G.g_message_from = 'white'
			)
		)
	ORDER BY G.g_date_created
";
$invites = $mysql->fetch_array($query, __LINE__, __FILE__);
$numInvites = $mysql->num_rows( );


// outvites ----------------------------------------------------------------

// if game is marked playerInvited and the invite is from the current player
// OR game is marked inviteDeclined and the response is from the opponent
$query = "
	SELECT G.*
		, UNIX_TIMESTAMP(G.g_date_created) AS u_date_created
		, P1.p_username AS white_username
		, P2.p_username AS black_username
		, 'outvite' AS outvite
		, 0 AS num_moves
	FROM ".T_GAME." AS G
		LEFT JOIN ".T_PLAYER." AS P1
			ON P1.p_id = G.g_white_player_id
		LEFT JOIN ".T_PLAYER." AS P2
			ON P2.p_id = G.g_black_player_id
	WHERE
		(
			G.g_game_message = 'Player Invited'
			AND (
				(
					G.g_white_player_id = '{$_SESSION['player_id']}'
					AND G.g_message_from = 'white'
				)
				OR (
					G.g_black_player_id = '{$_SESSION['player_id']}'
					AND G.g_message_from = 'black'
				)
			)
		)
		OR (
			G.g_game_message = 'Invite Declined'
			AND (
				(
					G.g_white_player_id = '{$_SESSION['player_id']}'
					AND G.g_message_from = 'black'
				)
				OR (
					G.g_black_player_id = '{$_SESSION['player_id']}'
					AND G.g_message_from = 'white'
				)
			)
		)
	ORDER BY G.g_date_created
";

$outvites = $mysql->fetch_array($query, __LINE__, __FILE__);
$numOutvites = $mysql->num_rows( );


// others ------------------------------------------------------------------

// generate a list of games with at least one move in it
$query = "
	SELECT DISTINCT h_game_id
		, COUNT(*) AS h_moves
	FROM ".T_HISTORY."
	GROUP BY h_game_id
";
$result = $mysql->fetch_array($query, __LINE__, __FILE__);

foreach ($result as $game)
{
	$others[] = $game['h_game_id'];
	$count[$game['h_game_id']] = $game['h_moves'];
}

$list = isset($others) ? implode(',',$others) : 0;

// now select all current games from that list
$query = "
	SELECT G.*
		, UNIX_TIMESTAMP(G.g_date_created) AS u_date_created
		, UNIX_TIMESTAMP(G.g_last_move) AS u_last_move
		, COUNT(H.h_time) - 1 AS num_moves
		, P1.p_username AS white_username
		, P2.p_username AS black_username
	FROM ".T_GAME." AS G
		LEFT JOIN ".T_HISTORY." AS H
			ON H.h_game_id = G.g_id
		LEFT JOIN ".T_PLAYER." AS P1
			ON P1.p_id = G.g_white_player_id
		LEFT JOIN ".T_PLAYER." AS P2
			ON P2.p_id = G.g_black_player_id
	WHERE G.g_game_message = ''
		AND G.g_id IN ({$list})
		AND G.g_white_player_id != '{$_SESSION['player_id']}'
		AND G.g_black_player_id != '{$_SESSION['player_id']}'
	GROUP BY G.g_id
	ORDER BY G.g_date_created
";
$others = $mysql->fetch_array($query, __LINE__, __FILE__);
$numOthers = $mysql->num_rows( );


// done --------------------------------------------------------------------

$query = "
	SELECT G.*
		, UNIX_TIMESTAMP(G.g_date_created) AS u_date_created
		, UNIX_TIMESTAMP(G.g_last_move) AS u_last_move
		, COUNT(H.h_time) - 1 AS num_moves
		, P1.p_username AS white_username
		, P2.p_username AS black_username
	FROM ".T_GAME." AS G
		LEFT JOIN ".T_HISTORY." AS H
			ON h_game_id = G.g_id
		LEFT JOIN ".T_PLAYER." AS P1
			ON P1.p_id = G.g_white_player_id
		LEFT JOIN ".T_PLAYER." AS P2
			ON P2.p_id = G.g_black_player_id
	WHERE G.g_game_message NOT IN ('','Player Invited','Invite Declined')
	GROUP BY G.g_id
	ORDER BY G.g_id
";
$done = $mysql->fetch_array($query, __LINE__, __FILE__);
$numDone = $mysql->num_rows( );


// other various bits ------------------------------------------------------

// get number of players
$query = "
	SELECT COUNT(*)
	FROM ".T_PLAYER."
";
$numPlayers = $mysql->fetch_value($query, __LINE__, __FILE__);

// get number of active games
$query = "
	SELECT COUNT(*)
	FROM ".T_GAME."
	WHERE g_game_message = ''
";
$numGames = $mysql->fetch_value($query, __LINE__, __FILE__);

// get number of total games
$query = "
	SELECT MAX(g_id)
	FROM ".T_GAME."
";
$totGames = $mysql->fetch_value($query, __LINE__, __FILE__);

$numMyturn = get_num_mine($active);
//*/

?>