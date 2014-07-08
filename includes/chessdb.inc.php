<?php

// get the required scripts
require_once 'config.inc.php';
//require_once 'chess.inc.php';
require_once 'chessutils.inc.php';
require_once 'webchessmail.inc.php';

/* these functions are used to interact with the DB */


function updateTimestamp( )
{
	global $mysql;

	$query = "
		UPDATE ".T_GAME."
		SET g_last_move = NOW( )
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$mysql->query($query, __LINE__, __FILE__);
}


// check the database to make sure that it knows about any mates (stale, or check)
function checkDatabase( )
{
	global $mysql, $chess;
	global $FENarray;

	call('Database Checked !!');

	$num_moves = count($FENarray) - 1;

	// get the current game message
	$query = "
		SELECT g_game_message
		FROM ".T_GAME."
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$message = $mysql->fetch_value($query, __LINE__, __FILE__);

	$FENbits = explode(' ', $FENarray[$num_moves]); // break up the FEN
	$color = ('w' == $FENbits[1]) ? 'black' : 'white';

# echo __FILE__ . ' : ' . __LINE__ . '<br />';
	$chess->init_gamestate($FENarray[$num_moves]);
	$state = $chess->get_status($FENbits[1]); // get the gamestate of the current FEN

	if (gsMate == $state && 'Checkmate' != $message)
	{
		$query = "
			UPDATE ".T_GAME."
			SET g_game_message = 'Checkmate'
				, g_message_from = '{$color}'
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);
		call('Checkmate entered');

		$query = "
			SELECT g_white_player_id
				, g_black_player_id
			FROM ".T_GAME."
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		list($white_id, $black_id) = $mysql->fetch_row($query, __LINE__, __FILE__);

		if ('white' == $color)
		{
			adjust_stats($white_id, $black_id, 1, 0);
		}
		else
		{
			adjust_stats($white_id, $black_id, 0, 1);
		}
	}
	elseif (gsStalemate == $state && 'Draw' != $message)
	{
		$query = "
			UPDATE ".T_GAME."
			SET g_game_message = 'Draw'
				, g_message_from = '{$color}'
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);
		call('Stalemate entered');

		$query = "
			SELECT g_white_player_id
				, g_black_player_id
			FROM ".T_GAME."
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		list($white_id, $black_id) = $mysql->fetch_row($query, __LINE__, __FILE__);

		adjust_stats($white_id, $black_id, 0.5, 0.5);
	}
}


function savePromotion( )
{
	global $mysql;
	global $movesArray, $FENarray, $curTurn;

	$num_moves = count($FENarray) - 1; // subtract one for initpos

	// when we run the promotion script, the color to be promoted
	// is the oppposite of the color who's turn it is
	$piece = $_POST['promotion'];

	if ('white' == $curTurn)
	{
		$piece = strtolower($piece);
	}

	// save the promoted piece in the movesArray
	$movesArray[$num_moves]['promo'] = $piece;

	// seperate the FEN board from the rest of the FEN
	$FEN     = $FENarray[$num_moves];
	$FENbits = trim(substr($FEN,strpos($FEN,' ')));
	$xFEN    = expandFEN(substr($FEN,0,strpos($FEN,' ')));

	// insert the promoted piece
	sqr2idx($movesArray[$num_moves]['toSq'], $idx);
	FENplace($xFEN, $idx, $movesArray[$num_moves]['promo']);

	// and repack the FEN
	$FENhead = packFEN($xFEN);
	$FEN     = $FENhead . ' ' . $FENbits;

	// and save the new and improved FEN to the history
	$query = "
		SELECT MAX(h_time)
		FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
	";
	$result = $mysql->fetch_value($query, __LINE__, __FILE__);

	$query = "
		UPDATE ".T_HISTORY."
		SET h_fen = '{$FEN}'
		WHERE h_game_id = '{$_SESSION['game_id']}'
			AND h_time = '{$result}'
	";
	$mysql->query($query, __LINE__, __FILE__);

	updateTimestamp( );
}


// loads the board var using the last FEN of the game
function loadGame( )
{
	global $mysql;
	global $board;

	$query = "
		SELECT h_fen
		FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
			AND h_time =
			(
				SELECT MAX(h_time)
				FROM ".T_HISTORY."
				WHERE h_game_id = '{$_SESSION['game_id']}'
			)
	";
	$temp = $mysql->fetch_value($query, __LINE__, __FILE__);

	$board = FEN2board($temp);
}


// saves the current FEN into the history table
function saveGame( )
{
	global $mysql, $chess;
	global $movesArray, $FENarray;
	global $oppColorArray, $colorArray;

	call('**** saveGame( ) ****');

	// convert the previous move to an FEN string
	$fullFEN = movetoFEN( ); // (chessutils.inc.php)

	// save the full FEN into the history table
	$query = "
		INSERT INTO ".T_HISTORY."
			(h_time, h_game_id, h_fen)
		VALUES
			(NOW( ), '{$_SESSION['game_id']}', '{$fullFEN}')
	";
	$mysql->query($query, __LINE__, __FILE__);

	// get the entire FEN list back out of the table for the email
	$query = "
		SELECT h_fen
		FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
		ORDER BY h_time
	";
	$FENarray = $mysql->fetch_value_array($query, __LINE__, __FILE__);

	// and convert the current FEN array to an array of standard moves for the email
	FENtomoves( ); // (chessutils.inc.php)

	// check for stalemates and checkmates
	// and update the games table if needed
	$FENbits = explode(' ', $fullFEN); // break up the FEN
	call($FENbits);
	call(__FILE__ . ' : ' . __LINE__);
	$chess->init_gamestate($fullFEN);
	$state = $chess->get_status($FENbits[1]); // get the gamestate of the current FEN

	$playerMoved = $oppColorArray[$FENbits[1]];
	$otherPlayer = $colorArray[$FENbits[1]];

	// if the game is over due to stalemate, or checkmate
	// make sure the database knows about it
	call("gameState = $state\ncheckmate = ".gsMate."\nstalemate = ".gsStalemate);
	if (gsStalemate == $state)
	{
		$query = "
			UPDATE ".T_GAME."
			SET g_game_message = 'Draw'
				, g_message_from = '{$_SESSION[$playerMoved]['p_color']}'
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);

		adjust_stats($_SESSION[$playerMoved]['p_id'], $_SESSION[$otherPlayer]['p_id'], 0.5, 0.5);
	}
	elseif (gsMate == $state)
	{
		$query = "
			UPDATE ".T_GAME."
			SET g_game_message = 'Checkmate'
				, g_message_from = '{$_SESSION[$playerMoved]['p_color']}'
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);

		adjust_stats($_SESSION[$playerMoved]['p_id'], $_SESSION[$otherPlayer]['p_id'], 1, 0);
	}

	// notify opponent of move via email (if we don't already know about, because we're right there)
	if ( ! isset($_SESSION['shared']) || ! $_SESSION['shared'])
	{
		call("webchessMail('move', {$_SESSION[$otherPlayer]['p_email']}, getMovesLongAlg(true), {$_SESSION[$playerMoved]['p_username']}, {$_SESSION['game_id']})");
		webchessMail('move', $_SESSION[$otherPlayer]['p_email'], getMovesLongAlg(true), $_SESSION[$playerMoved]['p_username'], $_SESSION['game_id']);
	}

	updateTimestamp( );
}


function processMessages( )
{
	global $mysql;
	global $isUndoRequested,$isDrawRequested,$undoing,$isGameOver,$isCheckMate;
	global $statusMessage,$CFG_USEEMAIL,$FENarray;
	global $colorArray;

	if (DEBUG) echo "Entering processMessages( )<br />\n";

	$num_moves = count($FENarray) - 1;

	$isUndoRequested = false;
	$isGameOver = false;

	// find out which player (black or white) we are serving
	if (DEBUG) echo "SharedPC... {$_SESSION['shared']}<br />\n";

	$FENitems = explode(' ',$FENarray[$num_moves]);
	$curTurn  = $colorArray[$FENitems[1]];

	if ($_SESSION['shared']) // Only the player to move is active in this case
	{
		if ($curTurn == $_SESSION['player']['p_color']) // if
			$currentPlayer = $_SESSION['player']['p_color'];
		else // The player who logged in later is to move
		{
			if ('white' == $_SESSION['player']['p_color'])
				$currentPlayer = 'black';
			else
				$currentPlayer = 'white';
		}
	}
	else // The players are on different computers
		$currentPlayer = $_SESSION['player']['p_color'];

	/* *********************************************** */
	/* queue user generated (ie: using forms) messages */
	/* *********************************************** */
	if (DEBUG) echo "Processing user generated (ie: form) messages...<br>\n";

	/* queue a request for an undo */
	if ( isset($_POST['requestUndo']) && 'yes' == $_POST['requestUndo'] && 0 != $num_moves )
	{
		/* if the two players are on the same system, execute undo immediately */
		/* NOTE: assumes the two players discussed it live before undoing */
		if ($_SESSION['shared'])
			$undoing = true;
		else
		{
			$query = "
				INSERT INTO ".T_MESSAGE."
					(m_game_id, m_type, m_status, m_destination)
				VALUES
					('{$_SESSION['game_id']}', 'undo', 'request', '{$_SESSION['opponent']['p_color']}')
			";
			$mysql->query($query, __LINE__, __FILE__);
			// ToDo: Mail an undo request notice to other player??
		}

		updateTimestamp( );
	}

	/* queue a request for a draw */
	if ( isset($_POST['requestDraw']) && 'yes' == $_POST['requestDraw'] )
	{
		/* if the two players are on the same system, execute Draw immediately */
		/* NOTE: assumes the two players discussed it live before declaring the game a draw */
		if ($_SESSION['shared'])
		{
			$query = "
				UPDATE ".T_GAME."
				SET g_game_message = 'Draw'
					, g_message_from = '{$currentPlayer}'
				WHERE g_id = '{$_SESSION['game_id']}'
			";
			$mysql->query($query, __LINE__, __FILE__);

			adjust_stats($_SESSION['white']['p_id'], $_SESSION['black']['p_id'], 0.5, 0.5);
		}
		else
		{
			$query = "
				INSERT INTO ".T_MESSAGE."
					(m_game_id, m_type, m_status, m_destination)
				VALUES
					('{$_SESSION['game_id']}', 'draw', 'request', '{$_SESSION['opponent']['p_color']}')
			";
			$mysql->query($query, __LINE__, __FILE__);
		}

		updateTimestamp( );
	}

	/* response to a request for an undo */
	if (isset($_POST['undoResponse']))
	{
		if ('yes' == $_POST['isUndoResponseDone'])
		{
			if ('yes' == $_POST['undoResponse'])
			{
				$status = 'approved';
				$undoing = true;
			}
			else
				$status = 'denied';

			$query = "
				UPDATE ".T_MESSAGE."
				SET m_status   = '{$status}'
					, m_destination = '{$_SESSION['opponent']['p_color']}'
				WHERE m_game_id = '{$_SESSION['game_id']}'
					AND m_type = 'undo'
					AND m_status = 'request'
					AND m_destination = '{$currentPlayer}'
			";
			$mysql->query($query, __LINE__, __FILE__);

			updateTimestamp( );
		}
	}

	/* response to a request for a draw */
	if (isset($_POST['drawResponse']))
	{
		if ('yes' == $_POST['isDrawResponseDone'])
		{
			if ('yes' == $_POST['drawResponse'])
			{
				$query = "
					UPDATE ".T_GAME."
					SET g_game_message = 'Draw'
						, g_message_from = '{$currentPlayer}'
					WHERE g_id = '{$_SESSION['game_id']}'
				";
				$mysql->query($query, __LINE__, __FILE__);
				$status = 'approved';

				adjust_stats($_SESSION['player']['p_id'], $_SESSION['opponent']['p_id'], 0.5, 0.5);
			}
			else
				$status = 'denied';

			$query = "
				UPDATE ".T_MESSAGE."
				SET m_status   = '{$status}'
					, m_destination = '{$_SESSION['opponent']['p_color']}'
				WHERE m_game_id = '{$_SESSION['game_id']}'
					AND m_type = 'draw'
					AND m_status = 'request'
					AND m_destination = '{$currentPlayer}'
			";
			$mysql->query($query, __LINE__, __FILE__);

			updateTimestamp( );
		}
	}

	/* resign the game */
	if (isset($_POST['resign']) && 'yes' == $_POST['resign'])
	{
		$query = "
			UPDATE ".T_GAME."
			SET g_game_message = 'Player Resigned'
				, g_message_from = '{$currentPlayer}'
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);

		updateTimestamp( );

		adjust_stats($_SESSION['player']['p_id'], $_SESSION['opponent']['p_id'], 0, 1);

		/* if email notification is activated... */
		if ($CFG_USEEMAIL && ! $_SESSION['shared'])
		{
			/* get opponent's player ID */
			if ('white' == $currentPlayer)
			{
				$query = "
					SELECT g_black_player_id
					FROM ".T_GAME."
					WHERE g_id = '{$_SESSION['game_id']}'
				";
			}
			else
			{
				$query = "
					SELECT g_white_player_id
					FROM ".T_GAME."
					WHERE g_id = '{$_SESSION['game_id']}'
				";
			}

			$opponentID = $mysql->fetch_value($query, __LINE__, __FILE__);

			$query = "
				SELECT p_email
				FROM ".T_PLAYER."
				WHERE p_id = '{$opponentID}'
			";
			$opponentEmail = $mysql->fetch_value($query, __LINE__, __FILE__);

			/* if opponent is using email notification... */
			if (0 < $mysql->num_rows( ))
			{
				if ('' != $opponentEmail)
				{
					/* notify opponent of resignation via email */
					call("webchessMail('resignation', $opponentEmail, '', {$_SESSION['username']}, {$_SESSION['game_id']})");
					webchessMail('resignation', $opponentEmail, '', $_SESSION['username'], $_SESSION['game_id']);
				}
			}
		}
	}


	/* ******************************************* */
	/* process queued messages (ie: from database) */
	/* ******************************************* */
	$query = "
		SELECT *
		FROM ".T_MESSAGE."
		WHERE m_game_id = '{$_SESSION['game_id']}'
		AND m_destination = '{$currentPlayer}'
	";
	$result = $mysql->fetch_array($query, __LINE__, __FILE__);

	foreach ($result as $message)
	{
		switch($message['m_type'])
		{
			case 'undo':
				switch($message['m_status'])
				{
					case 'request':
						$isUndoRequested = true;
						break;

					case 'approved':
						$query = "
							DELETE FROM ".T_MESSAGE."
							WHERE m_game_id = '{$_SESSION['game_id']}'
								AND m_type = 'undo'
								AND m_status = 'approved'
								AND m_destination = '{$currentPlayer}'
						";
						$mysql->query($query, __LINE__, __FILE__);
						$statusMessage .= "Undo approved";
						break;

					case 'denied':
						$undoing = false;
						$query = "
							DELETE FROM ".T_MESSAGE."
							WHERE m_game_id = '{$_SESSION['game_id']}'
								AND m_type = 'undo'
								AND m_status = 'denied'
								AND m_destination = '{$currentPlayer}'
						";
						$mysql->query($query, __LINE__, __FILE__);
						$statusMessage .= "Undo denied";
						break;
				}
				break;

			case 'draw':
				switch($message['m_status'])
				{
					case 'request':
						$isDrawRequested = true;
						break;

					case 'approved':
						$query = "
							DELETE FROM ".T_MESSAGE."
							WHERE m_game_id = '{$_SESSION['game_id']}'
								AND m_type = 'draw'
								AND m_status = 'approved'
								AND m_destination = '{$currentPlayer}'
						";
						$mysql->query($query, __LINE__, __FILE__);
						$statusMessage .= "Draw approved";
						break;

					case 'denied':
						$query = "
							DELETE FROM ".T_MESSAGE."
							WHERE m_game_id = '{$_SESSION['game_id']}'
								AND m_type = 'draw'
								AND m_status = 'approved'
								AND m_destination = '{$currentPlayer}'
						";
						$mysql->query($query, __LINE__, __FILE__);
						$statusMessage .= "Draw denied";
						break;
				}
				break;
		}
	}

	/* requests pending */
	$query = "
		SELECT *
		FROM ".T_MESSAGE."
		WHERE m_game_id = '{$_SESSION['game_id']}'
			AND m_status = 'request'
			AND m_destination = '{$_SESSION['opponent']['p_color']}'
	";
	$result = $mysql->fetch_array($query, __LINE__, __FILE__);

	foreach ($result as $message)
	{
		switch($message['m_type'])
		{
			case 'undo':
				$statusMessage .= "Your undo request is pending";
				break;

			case 'draw':
				$statusMessage .= "Your request for a draw is pending";
				break;
		}
	}

	/* game level status: draws, resignations and checkmate */
	/* if checkmate, update games table */
	$msgFr = ('white' == $curTurn) ? 'black' : 'white';
	$msgTo = ('white' == $curTurn) ? 'white' : 'black';
	if (isset($movesArray[$num_moves]['check']) && 'mate' == $movesArray[$num_moves]['check'])
	{
		$query = "
			UPDATE ".T_GAME."
			SET g_game_message = 'Checkmate'
				, g_message_from = '{$msgFr}'
			WHERE g_id = '{$_SESSION['game_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);

		adjust_stats($_SESSION['player']['p_id'], $_SESSION['opponent']['p_id'], 1, 0);

		// let the loser know the bad news
		call("webchessMail('checkmate', {$_SESSION[$msgTo]['p_email']}, '', {$_SESSION[$msgFr]['p_username']}, '')");
		webchessMail('checkmate', $_SESSION[$msgTo]['p_email'], '', $_SESSION[$msgFr]['p_username'], '');
	}

	$query = "
		SELECT g_game_message
			, g_message_from
		FROM ".T_GAME."
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$message = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	if ('Draw' == $message['g_game_message'])
	{
		$statusMessage .= "Game ended in a draw";
		$isGameOver = true;
	}

	if ('Player Resigned' == $message['g_game_message'])
	{
		$statusMessage .= $_SESSION[$message['g_message_from']]['p_username']." has resigned the game";
		$isGameOver = true;
	}

	if ('Checkmate' == $message['g_game_message'])
	{
		$statusMessage .= "Checkmate! {$_SESSION[$message['g_message_from']]['p_username']} has won the game";
		$isGameOver = true;
		$isCheckMate = true;
	}
}

// this function adjusts all the stats in the game
// based on the input
// updates ratings, wins, losses, draws, and streaks
//
// input player 1 id
// input player 2 id
// input player 1 result (1 = win, 0.5 = draw, 0 = loss)
// input player 2 result (1 = win, 0.5 = draw, 0 = loss)
function adjust_stats($p1_id, $p2_id, $S1, $S2)
{
	global $mysql, $CFG_RATING_STEP;

	$K = $CFG_RATING_STEP; // this can be changed (16 and 32 are most common)

	// get the current ratings for the players
	$query = "
		SELECT p_rating
		FROM ".T_PLAYER."
		WHERE p_id = '{$p1_id}'
	";
	$R1 = $mysql->fetch_value($query, __LINE__, __FILE__);

	$query = "
		SELECT p_rating
		FROM ".T_PLAYER."
		WHERE p_id = '{$p2_id}'
	";
	$R2 = $mysql->fetch_value($query, __LINE__, __FILE__);

	// calculate player 1's expected score against player 2
	$E1 = (1 / (1 + pow(10, (($R2 - $R1) / 400))));

	// calculate player 2's expected score against player 1
	$E2 = (1 / (1 + pow(10, (($R1 - $R2) / 400))));

	// calculate the adjusted rating for player 1
	$P1 = round($R1 + ($K * ($S1 - $E1)));

	// calculate the adjusted rating for player 2
	$P2 = round($R2 + ($K * ($S2 - $E2)));

	// update the ratings
	$query = "
		UPDATE ".T_PLAYER."
		SET p_rating = '{$P1}'
		WHERE p_id = '{$p1_id}'
	";
	$mysql->query($query, __LINE__, __FILE__);

	$query = "
		UPDATE ".T_PLAYER."
		SET p_rating = '{$P2}'
		WHERE p_id = '{$p2_id}'
	";
	$mysql->query($query, __LINE__, __FILE__);

	if (DEBUG)
	{
		call('---- RATING ADJUSTED ----');
		echo "<hr />
			OLD VALUE = $R1<br />
			OLD VALUE = $R2<br />
			<br />
			OUTCOME = $S1 - $S2<br />
			<br />
			NEW VALUE = $P1<br />
			NEW VALUE = $P2
			<hr />
		";
	}

	// now adjust the stats
	if ($S1 == $S2) // if it was a draw
	{
		// update the draws for the players
		$query = "
			UPDATE ".T_PLAYER."
			SET p_draws = p_draws + 1
			WHERE p_id IN ({$p1_id}, {$p2_id})
		";
		$mysql->query($query, __LINE__, __FILE__);

		// do nothing to the streaks
	}
	else // it was not a draw
	{
		if ($S1 > $S2) // if player 1 wins
		{
			$winner = $p1_id;
			$loser  = $p2_id;
		}
		else // if player 2 wins
		{
			$winner = $p2_id;
			$loser  = $p1_id;
		}

		// update the wins for the winning player
		$query = "
			UPDATE ".T_PLAYER."
			SET p_wins = p_wins + 1
			WHERE p_id = '{$winner}'
		";
		$mysql->query($query, __LINE__, __FILE__);

		// update the losses for the losing player
		$query = "
			UPDATE ".T_PLAYER."
			SET p_losses = p_losses + 1
			WHERE p_id = '{$loser}'
		";
		$mysql->query($query, __LINE__, __FILE__);

		// get the current streak for the winning player
		$query = "
			SELECT s_streak
			FROM ".T_STAT."
			WHERE s_id = '{$winner}'
				AND s_streak > 0
		";
		$mysql->query($query, __LINE__, __FILE__);

		// if there is a streak...
		if (0 != $mysql->num_rows( ))
		{
			// add to it
			$query = "
				UPDATE ".T_STAT."
				SET s_streak = s_streak + 1
				WHERE s_id = '{$winner}'
					AND s_streak > 0
			";
			$mysql->query($query, __LINE__, __FILE__);
		}
		else // if no streak yet...
		{
			// create one
			$query = "
				INSERT INTO ".T_STAT."
					(s_id, s_streak)
				VALUES
					('{$winner}', 1)
			";
			$mysql->query($query, __LINE__, __FILE__);
		}

		// stop the streak (if any) for the losing player
		$query = "
			UPDATE ".T_STAT."
			SET s_streak = -s_streak
			WHERE s_id = '{$loser}'
				AND s_streak > 0
		";
		$mysql->query($query, __LINE__, __FILE__);
	}
}

