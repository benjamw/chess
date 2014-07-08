<?php

// get required scripts
require_once 'chessutils.inc.php';
//require_once 'chess.inc.php';
require_once 'config.inc.php';


/* functions for outputting to html and javascript */
function getTurn( )
{
	global $perspective, $FENarray, $isPlayersTurn;

	$html = '';

	/* determine who's perspective of the board to show */
	if (isset($_SESSION['shared']) && $_SESSION['shared'] && ! $isPlayersTurn)
	{
		$perspective = ('white' == $_SESSION['player']['p_color']) ? 'black' : 'white';
	}
	else
	{
		$perspective = $_SESSION['player']['p_color'];
	}

	/* NOTE: if both players are using the same PC, in a sense it's always the players turn */
	if (isset($_SESSION['shared']) && $_SESSION['shared'])
	{
		$isPlayersTurn = true;
	}

	/* determine if board is disabled */
	$isDisabled = isBoardDisabled( );

	$perspective = (isset($perspective) && '' != $perspective) ? $perspective : 'white';

	$html .= "var isBoardDisabled = '{$isDisabled}';\n    ";
	$html .= "var isPlayersTurn = '{$isPlayersTurn}';\n    ";
	$html .= "var perspective = '{$perspective}';\n    ";

	return $html;
}



/* provide FEN data to javascript function */
function getJSFEN( )
{
	global $FENarray, $gameResult;

	$html = 'var FEN = [';

	for ($i = 0; $i < count($FENarray); $i++)
	{
		if (0 < $i) $html .= ","; // only put commas after the first FEN
		if (0 == ($i % 6)) $html .= "\n      ";
		$html .= "'{$FENarray[$i]}'";
	}

	$gameResult = (isset($gameResult)) ? $gameResult : '';
	$html .= "\n    ];\n    "
			."var result = '{$gameResult}';\n    ";

	return $html;
}





function getMoves($method = false)
{
	// movesArray is a 2D array that contains, for every move:
	// $movesArray[$i] which contains an array that consists of:
	// -- ALWAYS --
	//   'piece'   = the PGN code of the piece that was moved, ie. k for black king, or R for white rook
	//   'fromSq'  = the FROM square counted a1 to h8 as 0 to 63
	//   'fromRow' = the FROM rank counted 1 to 8 as 0 to 7
	//   'fromCol' = the FROM file counted a to h as 0 to 7
	//   'toSqr'   = the TO square
	//   'toRow'   = the TO rank
	//   'toCol'   = the TO file
	// -- SOMETIMES --
	//   'captSqr' = the same as the TO square above unless en passant, then it is the captured pawn square
	//   'captRow' = the same as the TO rank above unless en passant, then it is the captured pawn rank
	//   'captCol' = the same as the TO file above unless en passant, then it is the captured pawn file
	//   'captPiece' = the PGN code of the piece that was captured
	//   'extra'   = contains either 'ep' for en passant, 'O-O-O', or 'O-O' for castle moves
	//   'promo'   = the PGN code of the piece that the pawn promoted to
	//   'check'   = contains check information as either 'check' or 'mate'

	/* based on player's preferences, display the history */
	$moves = array( );  // Make sure that $moves is defined

	$html = '';

	if ( ! isset($_SESSION['pref_history']))
	{
		$_SESSION['pref_history'] = 'pgn';
	}

	$method = (false !== $method) ? $method : $_SESSION['pref_history'];

	switch ($method)
	{
		case 'verbous':
			$moves = getMovesVerbous( );
			break;

		case 'coord':
			$moves = getMovesCoordinate( );
			break;

		case 'alg':
			$moves = getMovesAlg( );
			break;

		case 'desc': // way too hard right now, but go ahead
			$moves = getMovesDescriptive( );
			break;

		case 'int':
			$moves = getMovesInternational( );
			break;

		case 'pgn':
		case 'longalg':
		default:
			$moves = getMovesLongAlg( );
			break;
	}

	$comma = '';
	$html .= "var moves = [";

	for ($i = 0; $i < count($moves); $i++)
	{
			$html .= $comma;
			if ((($i - 1) % 4) == 0) // Four moves on each line
			{
				$html .= "\n      ";
			}

			$html .= "['" . $moves[$i][0]."','";

			if ( isset($moves[$i][1]) )
			{
				$html .= $moves[$i][1];
			}

			$html .= "']";
			$comma = ",";
	}

	$html .= "\n    ];\n    ";

	return $html;
}


function getStatus( )
{
	global $movesArray, $isCheckMate, $statusMessage, $isPlayersTurn;

	$html = '';

	$num_moves = count($movesArray) - 1;

	if ($isPlayersTurn)
	{
		$html .= "var whosMove = 'Your Turn';\n    ";
	}
	else
	{
		$html .= "var whosMove = 'Opponent\'s Turn';\n    ";
	}

	$curColor = ( ($num_moves == -1) || ($num_moves % 2 == 1) ) ? 'White' : 'Black';

	$html .= "var gameState = '";

	if (isset($movesArray[$num_moves]['check']))
	{
		$html .= $movesArray[$num_moves]['check'];
	}

	$html .= "';\n    ";
	$html .= "var statusMessage = '{$statusMessage}';\n    ";

	return $html;
}


function getPromotion( )
{
	$html = '

	<div class="gameinput">
		Promote pawn to:<br />
		<label for="promotionQ"><input type="radio" name="promotion" id="promotionQ" value="'.Q.'" checked="checked">Queen</label>
		<label for="promotionR"><input type="radio" name="promotion" id="promotionR" value="'.R.'">Rook</label>
		<label for="promotionN"><input type="radio" name="promotion" id="promotionN" value="'.N.'">Knight</label>
		<label for="promotionB"><input type="radio" name="promotion" id="promotionB" value="'.B.'">Bishop</label>
		<input type="button" name="btnPromote" value="Promote" onClick="promotepawn( )" />
	</div>
	';

	return $html;
}


function getUndoRequest( )
{
	$html = '

	<div class="gameinput">
		Your opponent would like to undo the last move.  Will you allow it?<br />
		<label for="undoResponseY"><input type="radio" name="undoResponse" id="undoResponseY" value="yes">Yes</label> | <label for="undoResponseN"><input type="radio" name="undoResponse" id="undoResponseN" value="no" checked="checked">No</label>
		<input type="hidden" name="isUndoResponseDone" value="no">
		<input type="button" value="Reply" onClick="this.form.isUndoResponseDone.value = \'yes\'; this.form.submit( )">
	</div>
	';

	return $html;
}


function getDrawRequest( )
{
	$html = '

	<div class="gameinput">
		Your opponent is offering a draw.  Do you accept?<br />
		<label for="drawResponseY"><input type="radio" name="drawResponse" id="drawResponseY" value="yes">Yes</label> | <label for="drawResponseN"><input type="radio" name="drawResponse" id="drawResponseN" value="no" checked="checked">No</label>
		<input type="hidden" name="isDrawResponseDone" value="no">
		<input type="button" value="Reply" onClick="this.form.isDrawResponseDone.value = \'yes\'; this.form.submit( )">
	</div>
	';

	return $html;
}


function getPGN( )
{
	// the PGN export format is very exact when it comes to what is allowed
	// and what is not allowed when creating a PGN file.
	// first, the only new line character that is allowed is a single line feed character
	// output in PHP as \n, this means that \r is not allowed, nor is \r\n
	// second, no tab characters are allowed, neither vertical, nor horizontal (\t)
	// third, comments do NOT nest, thus { { } } will be in error, as will { ; }
	// fourth, { } denotes an inline comment, where ; denotes a 'rest of line' comment
	// fifth, a percent sign (%) at the beginning of a line denotes a whole line comment
	// sixth, comments may not be included in the meta tags ( [Meta "data"] )

	global $mysql;
	global $_SESSION,$FENarray,$movesArray,$pWhite,$pWhiteF,$pWhiteL;
	global $pBlack,$pBlackF,$pBlackL,$gStart,$CFG_SITENAME;

		// get ELO's for the players
	$query = "
		SELECT *
		FROM ".T_GAME."
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$game = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	$query = "
		SELECT p_rating
		FROM ".T_PLAYER."
		WHERE p_id = '{$game['g_black_player_id']}'
	";
	$pBlackR = $mysql->fetch_value($query, __LINE__, __FILE__);

	$query = "
		SELECT p_rating
		FROM ".T_PLAYER."
		WHERE p_id = '{$game['g_white_player_id']}'
	";
	$pWhiteR = $mysql->fetch_value($query, __LINE__, __FILE__);

	$num_moves = count($FENarray) - 1;

	$FEN = $FENarray[0];

	$moves = getMovesAlg( );

	$gStart = date('Y.m.d', $gStart);

	$xheader = "[Event \"WebChess Casual Game #{$_SESSION['game_id']}\"]\n"
					 . "[Site \"{$CFG_SITENAME}\"]\n"
					 . "[Date \"$gStart\"]\n"
					 . "[Round \"-\"]\n"
					 . "[White \"$pWhiteL, $pWhiteF\"]\n"
					 . "[Black \"$pBlackL, $pBlackF\"]\n"
					 . "[WhiteElo \"$pWhiteR\"]\n"
					 . "[BlackElo \"$pBlackR\"]\n";

	$xheadxtra = "[Mode \"ICS\"]\n";

	if (518 != $_SESSION['id960'])
		$xheadxtra .= "[SetUp \"1\"]\n[FEN \"$FEN\"]\n";

	$body     = '';
	$bodyLine = '';

	foreach ($moves as $key => $move)
	{
		$token = ($key + 1) . '. ' . $move[0];

		if (isset($move[1]))
		{
			$token .= ' ' . $move[1];
		}

		if ( ( strlen($bodyLine) + strlen($token) ) > 79 )
		{
				$body .= $bodyLine . "\n";
				$bodyLine = '';
		}
		elseif ( strlen($bodyLine) > 0 )
		{
				$bodyLine .= ' ';
		}

		$bodyLine .= $token;
		$token = '';
	}

	// finish up the PGN with the game result
	$query = "
		SELECT g_game_message
			, g_message_from
		FROM ".T_GAME."
		WHERE g_id = '{$_SESSION['game_id']}'
	";
	$message = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	if ('white' == $message['g_message_from'])
	{
		if ('Player Resigned' == $message['g_game_message']) // losing messages
			$result = '0-1';
		elseif ('Checkmate' == $message['g_game_message']) // winning messages
			$result = '1-0';
		elseif ('Draw' == $message['g_game_message']) // draw messages
			$result = '1/2-1/2';
	}
	elseif ('black' == $message['g_message_from'])
	{
		if ('Player Resigned' == $message['g_game_message']) // losing messages
			$result = '1-0';
		elseif ('Checkmate' == $message['g_game_message']) // winning messages
			$result = '0-1';
		elseif ('Draw' == $message['g_game_message']) // draw messages
			$result = '1/2-1/2';
	}
	else
		$result = '*';

	$body .= $bodyLine;

	if ( ( strlen($bodyLine) + strlen($result) ) > 79 )
		$body .= "\n";
	elseif ( strlen($bodyLine) > 0 )
		$body .= ' ';

	$body .= $result . "\n";
	$xheader .= "[Result \"$result\"]\n";

	return $xheader . $xheadxtra . "\n" . $body;
}


//******************************************************************************
//  get move notations
//******************************************************************************

// These function convert the $movesArray data to human readable moves
// contained in an array called $moves which is then ouput by getMoves( )
// to the javascript for display in the moves table
function getMovesVerbous( )
{
	global $movesArray, $pieceName;

	$moves = array( );

	for ($i = 0; $i < (count($movesArray) - 1); $i++)
	{
		$move = $movesArray[$i + 1];

		// clear out all of the vars
		$piece = $sqFrom = $sqTo = $mid = $pro = $chk = '';

		$piece = $pieceName[$move['piece']];
		colrow2til($move['fromCol'],$move['fromRow'],$sqFrom);
		colrow2til($move['toCol'],$move['toRow'],$sqTo);
		$mid = isset($move['captSq']) ? ' captured ' . $pieceName[$move['captPiece']] . ' on ' : ' to ';
		$pro = isset($move['promo']) ? " promoted to {$pieceName[$move['promo']]}" : '';

		if (isset($move['check']))
		{
			if ('check' == $move['check'])
				$chk = ", check";
			elseif ('mate' == $move['check'])
				$chk = ", checkmate";
		}

		// if it's a castle move
		if (isset($move['extra']) && 'ep' != $move['extra'])
		{
			if ('O-O-O' == $move['extra'])
				$moves[floor($i / 2)][$i % 2] = 'castle a-side' . $chk; // just display the castle notation
			else
				$moves[floor($i / 2)][$i % 2] = 'castle h-side' . $chk; // just display the castle notation
		}
		elseif (isset($move['extra']) && 'ep' == $move['extra']) // it's an en passant move
			$moves[floor($i / 2)][$i % 2] = $piece . ' from ' . $sqFrom . $mid . $sqTo . ' en passant' . $chk; // display it
		else // it's a normal move
			$moves[floor($i / 2)][$i % 2] = $piece . ' from ' . $sqFrom . $mid . $sqTo . $pro . $chk; // display it
	}

	return $moves;
}

function getMovesCoordinate( )
{
	global $movesArray;

	$moves = array( );

	for ($i = 0; $i < (count($movesArray) - 1); $i++)
	{
		$move = $movesArray[$i + 1];

		// clear out all of the vars
		$piece = $sqFrom = $sqTo = $mid = $pro = $chk = '';

		colrow2til($move['fromCol'],$move['fromRow'],$sqFrom);
		colrow2til($move['toCol'],$move['toRow'],$sqTo);
		$mid = isset($move['captSq']) ? 'x' : '-';
		$pro = isset($move['promo']) ? '=' . strtolower($move['promo']) : '';

		if ( isset($move['check']) )
		{
			if ('check' == $move['check'])
				$chk = '+';
			elseif ('mate' == $move['check'])
				$chk = '#';
		}

		// if it's a castle move
		if (isset($move['extra']) && 'ep' != $move['extra'])
			$moves[floor($i / 2)][$i % 2] = $move['extra'] . $chk; // just display the castle notation
		elseif (isset($move['extra']) && 'ep' == $move['extra']) // it's an en passant move
			$moves[floor($i / 2)][$i % 2] = $sqFrom . $mid . $sqTo . 'ep' . $chk; // display it
		else // it's a normal move
			$moves[floor($i / 2)][$i % 2] = $sqFrom . $mid . $sqTo . $pro . $chk; // display it
	}

	return $moves;
}


function getMovesAlg( )
{
	global $movesArray;

	$moves = array( );

	for ($i = 0; $i < (count($movesArray) - 1); ++$i)
	{
		$move = $movesArray[$i + 1];

		// clear out all of the vars
		$piece = $sqFrom = $sqTo = $mid = $pro = $chk = '';

		$piece = str_replace('P','',strtoupper($move['piece']));

		$sqFrom = clearAmbiguity($i + 1);

		colrow2til($move['toCol'],$move['toRow'],$sqTo);
		$mid = isset($move['captSq']) ? 'x' : '';
		$pro = isset($move['promo']) ? "={$move['promo']}" : '';

		if ( isset($move['check']) )
		{
			if ('check' == $move['check'])
				$chk = '+';
			elseif ('mate' == $move['check'])
				$chk = '#';
		}

		// if it's a castle move
		if (isset($move['extra']) && 'ep' != $move['extra'])
			$moves[floor($i / 2)][$i % 2] = $move['extra'] . $chk; // just display the castle notation
		elseif (isset($move['extra']) && 'ep' == $move['extra']) // it's an en passant move
			$moves[floor($i / 2)][$i % 2] = $piece . $sqFrom . $mid . $sqTo . 'ep' . $chk; // display it
		else // it's a normal move
			$moves[floor($i / 2)][$i % 2] = $piece . $sqFrom . $mid . $sqTo . $pro . $chk; // display it
	}

	return $moves;
}

function getMovesInternational( )
{
	global $movesArray,$COLS;

	$moves = array( );

	for ($i = 0; $i < (count($movesArray) - 1); $i++)
	{
		$move = $movesArray[$i + 1];

		// clear out all of the vars
		$sqFrom = $sqTo = $pro = '';

		colrow2til($move['fromCol'],$move['fromRow'],$sqFrom);
		colrow2til($move['toCol'],$move['toRow'],$sqTo);

		if (isset($move['promo']))
		{
			switch (strtoupper($move['promo']))
			{
				case 'Q': $pro = 1; break;
				case 'R': $pro = 2; break;
				case 'B': $pro = 3; break;
				case 'N': $pro = 4; break;
			}
		}

		$sqFrom = (strpos($COLS,substr($sqFrom,0,1)) + 1) . substr($sqFrom,1,1);
		$sqTo   = (strpos($COLS,substr($sqTo,0,1)) + 1) . substr($sqTo,1,1);

		if ('' != $pro)
			$sqTo = substr($sqTo,0,1) . $pro;

		$moves[$i/2][$i % 2] = $sqFrom . $sqTo; // display it
	}

	return $moves;
}

function getMovesLongAlg($last = false)
{
	global $movesArray;

	$moves = array( );

	for ($i = 0; $i < (count($movesArray) - 1); $i++)
	{
		if ($last)
		{
			$i = count($movesArray) - 2; // subtract 2 because we add one below
		}

		$move = $movesArray[$i + 1];

		// clear out all of the vars
		$piece = $sqFrom = $sqTo = $mid = $pro = $chk = '';

		if (!isset($move['piece']))
			call($move);

		$piece = str_replace('P', '', strtoupper($move['piece']));
		colrow2til($move['fromCol'],$move['fromRow'],$sqFrom);
		colrow2til($move['toCol'],$move['toRow'],$sqTo);
		$mid = isset($move['captSq']) ? 'x' : '-';
		$pro = isset($move['promo']) ? "={$move['promo']}" : '';

		if ( isset($move['check']) )
		{
			if ('check' == $move['check'])
				$chk = '+';
			elseif ('mate' == $move['check'])
				$chk = '#';
		}

		if (isset($move['extra']) && 'ep' != $move['extra']) // if it's a castle move
			$moves[floor($i / 2)][$i % 2] = $move['extra'] . $chk; // just display the castle notation
		elseif (isset($move['extra']) && 'ep' == $move['extra']) // if it's an en passant move
			$moves[floor($i / 2)][$i % 2] = $piece . $sqFrom . $mid . $sqTo . 'ep' . $chk; // display it
		else // if it's a normal move
			$moves[floor($i / 2)][$i % 2] = $piece . $sqFrom . $mid . $sqTo . $pro . $chk; // display it
	}

	if (DEBUG && $last) { call($moves); call(floor($i / 2)); call($i % 2);}

	if ($last)
	{
		$i--; // reset $i from the $i++ in the for loop parameters
		return $moves[floor($i / 2)][$i % 2];
	}
	else
	{
		return $moves;
	}
}

?>