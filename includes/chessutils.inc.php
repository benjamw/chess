<?php

require_once 'chessconstants.inc.php';

/* these are utility functions used by other functions */


function getPieceCode($color,$piece)
{
	switch($piece)
	{
		case 'pawn':   $code = PAWN;   break;
		case 'knight': $code = KNIGHT; break;
		case 'bishop': $code = BISHOP; break;
		case 'rook':   $code = ROOK;   break;
		case 'queen':  $code = QUEEN;  break;
		case 'king':   $code = KING;   break;
	}

	if ('black' == $color)
		$code = BLACK | $code;

	return $code;
}


function getPGNCode($piecename)
{
	switch ($piecename)
	{
		case 'knight': $pgnCode = "N"; break;
		case 'bishop': $pgnCode = "B"; break;
		case 'rook':   $pgnCode = "R"; break;
		case 'queen':  $pgnCode = "Q"; break;
		case 'king':   $pgnCode = "K"; break;
		case 'pawn':
		case '':
		default:       $pgnCode = "";  break;
	}

	return $pgnCode;
}


function isBoardDisabled( )
{
	global $board, $promoting, $isUndoRequested, $isDrawRequested, $isGameOver;

	/* if current player is promoting, a message needs to be replied to (Undo or Draw), or the game is over, then board is Disabled */
	$isBoardDisabled = (($promoting || $isUndoRequested || $isDrawRequested || $isGameOver) == true);

	/* if opponent is in the process of promoting, then board is disabled */
	if (!$isBoardDisabled)
	{
		if ($_SESSION['player']['p_color'] == "white")
			$promotionRow = 7;
		else
			$promotionRow = 0;

		for ($i = 0; $i < 8; ++$i)
			if (($board[$promotionRow][$i] & COLOR_MASK) == PAWN)
				$isBoardDisabled = true;
	}

	return $isBoardDisabled;
}


// this function returns a fromSqr as a tile (possibly only one character)
// to remove any ambiguity from the move
function clearAmbiguity($n = 'NaN')
{
	global $FENarray, $movesArray;

	$n = ('NaN' !== $n) ? $n : count($FENarray) - 1;
	$piece = $movesArray[$n]['piece'];
	$found = array( );

	// if there is no move, there is no ambiguity
	if (0 == $n)
	{
		return '';
	}

	// there can always be only one king
	if ('K' == strtoupper($piece))
	{
		return '';
	}

	// if it was a pawn move, but not a capture
	if (('P' == strtoupper($piece)) && ! isset($movesArray[$n]['captSq']))
	{
		return '';
	}
	elseif (('P' == strtoupper($piece)) && isset($movesArray[$n]['captSq']))
	{
		// we always return a sqFrom for pawn captures
		// but just the letter
		return substr(sqr2til($movesArray[$n]['fromSq'], $til), 0, 1);
	}

	// it is possible to have more than one queen, bishop on the same color,
	// rook, and knight move to the same square

	// set the moves arrays [dist][vert][horz]
	$bm = array(7,array(1,1,-1,-1),array(-1,1,1,-1)); // bishops
	$nm = array(1,array(1,2,2,1,-1,-2,-2,-1),array(-2,-1,1,2,2,1,-1,-2)); // knights (note special 2s)
	$rm = array(7,array(1,0,-1,0),array(0,1,0,-1)); // rooks
	$qm = array(7,array(1,1,1,0,-1,-1,-1,0),array(-1,0,1,1,1,0,-1,-1)); // queens

	// get a searchable FEN string
	$xFEN = expandFEN($FENarray[$n]);

	// search the xFEN for any other piece that may have moved into the toSqr
	// first get the correct moves array
	eval('$ma = $'.strtolower($piece).'m;');

	$col = $movesArray[$n]['toCol'];
	$row = $movesArray[$n]['toRow'];

	// as far as we can go
	for ($j = 1; $j <= $ma[0]; ++$j)
	{
		// for each direction
		for ($i = 0; $i < count($ma[1]); ++$i)
		{
			// calculate the x and y location we are testing
			$x = $col + ($j * $ma[1][$i]);
			$y = $row + ($j * $ma[2][$i]);

			// if we are outside the board
			if (((0 > $x) || ($x > 7)) || ((0 > $y) || ($y > 7)))
			{
				continue; // skip to the next direction
			}

			colrow2idx($x, $y, $idx);

			$item = substr($xFEN, $idx, 1);

			// if we found it, save the data
			if ($piece == $item)
			{
				$found[] = $idx;
			}
			elseif (('0' != $item) && ('N' != strtoupper($piece))) // else if we found a blocking piece (unless testing knights)
			{
				continue; // skip to the next direction
			}
		}
	}

	if (0 != count($found))
	{
		$from_til = sqr2til($movesArray[$n]['fromSq'], $from_til);

		if (1 == count($found))
		{
			$amb_til = idx2til($found[0], $amb_til);

			// compare them
			// if the files are the same
			if ($amb_til[0] == $from_til[0])
			{
				//use the rank
				return $from_til[1];
			}
			else // the files are not the same
			{
				// use the file
				return $from_til[0];
			}
		}
		elseif (2 <= count($found))
		{
			$rank = $file = false;

			// compare files and ranks and determining which to use, or both
			foreach ($found as $piece)
			{
				$amb_til = idx2til($piece, $amb_til);

				// if the files are the same
				if ($amb_til[0] == $from_til[0])
				{
					// let us know
					$file = true;
				}
				elseif ($amb_til[1] == $from_til[1]) // if the ranks are the same
				{
					// let us know
					$rank = true;
				}
			}

			// if the pieces are not on the same file
			if (false === $file)
			{
				return $from_til[0]; // return the file
			}
			elseif (false === $rank) // if not on the same rank
			{
				return $from_til[1]; // return the rank
			}
			else // there are pieces on the same rank, and on the same file
			{
				return $from_til; // return the entire tile
			}
		}
	}
	else
	{
		return '';
	}
}


function returnGameInfo($game_id)
{
	global $mysql;
	global $pWhite,$pWhiteF,$pWhiteL,$pBlack,$pBlackF,$pBlackL,$gStart,$MyColor,$isDraw;

	$query = "
		SELECT g_white_player_id
			, g_black_player_id
			, UNIX_TIMESTAMP(g_date_created) AS g_date_created
			, g_game_message
		FROM ".T_GAME."
		WHERE g_id = '{$game_id}'
	";
	$game = $mysql->fetch_assoc($query, __LINE__, __FILE__);

	$gStart = $game['g_date_created'];
	$isDraw='';

	if ($game['g_game_message'] == 'Draw')
		$isDraw = true;
	else
		$isDraw = '';

	$query = "
		SELECT p_username
			, p_first_name
			, p_last_name
		FROM ".T_PLAYER."
		WHERE p_id = '{$game['g_black_player_id']}'
	";
	$xBlack = $mysql->fetch_assoc($query, __LINE__, __FILE__);
	$pBlack = $xBlack['p_username'];
	$pBlackF = $xBlack['p_first_name'];
	$pBlackL = $xBlack['p_last_name'];

	$query = "
		SELECT p_username
			, p_first_name
			, p_last_name
		FROM ".T_PLAYER."
		WHERE p_id = '{$game['g_white_player_id']}'
	";
	$xWhite = $mysql->fetch_assoc($query, __LINE__, __FILE__);
	$pWhite = $xWhite['p_username'];
	$pWhiteF = $xWhite['p_first_name'];
	$pWhiteL = $xWhite['p_last_name'];

	if ( isset($_SESSION['player_id']) && $game['g_white_player_id'] == $_SESSION['player_id'] )
	{
			$MyColor="white";
	}
	elseif ( isset($_SESSION['player_id']) && $game['g_black_player_id'] == $_SESSION['player_id'] )
	{
			$MyColor="black";
	}
	else
	{
			$MyColor="none";
	}
}


// converts the given id960 to an initpos
function id960_to_pos($id960 = "")
{
	// use normal game id960 if none given (0 is valid id, so use ===)
	$id960 = ( "" === $id960 ) ? 518 : $id960;

	// init string
	$pos = "--------";

	// place bishops
	$pos = substr_replace($pos,"B",(($id960 % 4) * 2) + 1,1); // light
	$id960 = floor($id960 / 4);

	$pos = substr_replace($pos,"B",(($id960 % 4) * 2),1); // dark
	$id960 = floor($id960 / 4);

	// place queen
	$j = -1;$k = 0;
	for ($i = 0; $i < 8; ++$i)
	{
		// count the empty spaces (and filled ones)
		('-' == substr($pos,$i,1)) ? ++$j : ++$k;

		// if we are at the spot we need, place the piece and quit
		if ( ($id960 % 6) == $j )
		{ // j = empty spaces (minus one); k = filled spaces; add them for proper php position (begins with 0)
			$pos = substr_replace($pos, 'Q', $j + $k, 1);
			break;
		}
	}
	$id960 = floor($id960 / 6);

	// place knights, rooks, and king
	$krn = array('NNRKR','NRNKR','NRKNR','NRKRN','RNNKR','RNKNR','RNKRN','RKNNR','RKNRN','RKRNN');

	$j = 0;
	for ( $i = 0; $i < 8; ++$i )
	{
		// if we're at an empty spot...
		if ( '-' == substr($pos,$i,1) )
		{
			// fill it with the jth item in the KRN string
			$pos = substr_replace($pos,substr($krn[$id960],$j,1),$i,1);
			++$j; // then increment j
		}
		if ( 5 == $j ) break;
	}

	return $pos;
}


function FEN2board($FEN)
{
	$board = array( );
	$xFEN = expandFEN($FEN);
	$k = 0;
	for ($i = 7; $i >= 0; --$i) // black to white
	{
		for ($j = 0; $j < 8; ++$j) // left to right
		{
			// board[row][col]
			$board[$i][$j] = substr($xFEN, $k++, 1);
		}
	}
	return $board;
}


// this function converts the FENarray of moves to a movesArray
// containing all relevant information about the move
// NOTE: the ouput of the castle from and to squares
// is different from the javascript version of this function
function FENtomoves( )
{
	global $chess;
	global $FENarray, $movesArray;

	$files = 'abcdefgh';

	if (1 == count($FENarray)) // prevent the script from running without any information
	{
		return false;
	}

	$movesArray = array( );
	$movesArray[0] = "No move made yet";

	for ($i = 1; $i < count($FENarray); $i++) // start at 1 because the first FEN is the start position
	{
		// clear out the previous capture vars
		unset($captIdx);

		$thisFEN = explode(' ', $FENarray[$i]);
		$thatFEN = explode(' ', $FENarray[$i - 1]);

		$thisBoard = expandFEN($thisFEN[0]);
		$thatBoard = expandFEN($thatFEN[0]);

		$ep = $thatFEN[3];
		$epSqr = strpos( $files, substr($ep,0,1) ) + ( ( substr($ep,1,1) - 1) * 8);

		if (0 <= $epSqr && 63 >= $epSqr)
			idx2sqr($epSqr,$epIdx);
		else
			$epIdx = 75; // nothing near the board

		// start by checking for a castle move
		// this may not be the best way to go about it, but it's all i've got right now
		if ('w' == $thatFEN[1] // it was white's move
			&& ( false !== strpos($thatFEN[2],'K') || false !== strpos($thatFEN[2],'Q')) // and they could have castled
			&& ( false === strpos($thisFEN[2],'K') && false === strpos($thisFEN[2],'Q'))) // and now they can't
		{
			$backRank = substr($thisBoard,-8,8);

			// check for proper piece position
			if ('K' == substr($backRank,2,1) && 'R' == substr($backRank,3,1) && false !== strpos($thatFEN[2],'Q'))
			{
				$movesArray[$i]['piece'] = 'K';
				$movesArray[$i]['extra'] = 'O-O-O';
				$fromIdx = strpos($thatBoard,'K'); // the king's starting position
				$toIdx   = 58; // the king's final position
			}
			elseif ('K' == substr($backRank,6,1) && 'R' == substr($backRank,5,1) && false !== strpos($thatFEN[2],'K'))
			{
				$movesArray[$i]['piece'] = 'K';
				$movesArray[$i]['extra'] = 'O-O';
				$fromIdx = strpos($thatBoard,'K'); // the king's starting position
				$toIdx   = 62; // the king's final position
			}
		}
		elseif ('b' == $thatFEN[1] // it was black's move
			&& ( false !== strpos($thatFEN[2],'k') || false !== strpos($thatFEN[2],'q')) // and they could have castled
			&& ( false === strpos($thisFEN[2],'k') && false === strpos($thisFEN[2],'q'))) // and now they can't
		{
			$backRank = substr($thisBoard,0,8);

			// check for proper piece position
			if ('k' == substr($backRank,2,1) && 'r' == substr($backRank,3,1) && false !== strpos($thatFEN[2],'q'))
			{
				$movesArray[$i]['piece'] = 'k';
				$movesArray[$i]['extra'] = 'O-O-O';
				$fromIdx = strpos($thatBoard,'k'); // the king's starting position
				$toIdx   = 2; // the king's final position
			}
			elseif ('k' == substr($backRank,6,1) && 'r' == substr($backRank,5,1) && false !== strpos($thatFEN[2],'k'))
			{
				$movesArray[$i]['piece'] = 'k';
				$movesArray[$i]['extra'] = 'O-O';
				$fromIdx = strpos($thatBoard,'k'); // the king's starting position
				$toIdx   = 6; // the king's final position
			}
		}

		if ( ! isset($movesArray[$i]['extra'])) // if not castling, get the FROM square and TO square
		{
			// check for en passant captures first
			if ('w' == $thatFEN[1])
			{
				if (75 != $epIdx && 'P' == substr($thisBoard,$epIdx,1)) // white capture black en passant
				{
					$captIdx = $epIdx + 8;
					$captPiece = 'p';
					$movesArray[$i]['extra'] = 'ep';
				}
			}
			else // black's turn
			{
				if (75 != $epIdx && 'p' == substr($thisBoard,$epIdx,1)) // black capture white en passant
				{
					$captIdx = $epIdx - 8;
					$captPiece = 'P';
					$movesArray[$i]['extra'] = 'ep';
				}
			}

			// then go through every square
			for ($j = 0; $j < 64; $j++)
			{
				// exclude any en passant capture squares from the search, and look for differences
				if (( ! isset($captIdx) || $j != $captIdx) && substr($thisBoard,$j,1) != substr($thatBoard,$j,1))
				{
					// if the current board has a 1, then it must be the from square
					if ('0' == substr($thisBoard,$j,1))
						$fromIdx = $j;
					else // it is the to square
					{
						$toIdx = $j;
						$movesArray[$i]['piece'] = substr($thisBoard,$j,1);
					}
				}
			}

			// check for pawn promotions
			if ((((56 <= $toIdx && 63 >= $toIdx) && 'p' == substr($thatBoard,$fromIdx,1))
				|| ((0  <= $toIdx && 7  >= $toIdx) && 'P' == substr($thatBoard,$fromIdx,1)))
				&& (substr($thatBoard,$fromIdx,1) != substr($thisBoard,$toIdx,1)))
			{
				$movesArray[$i]['promo'] = strtoupper(substr($thisBoard,$toIdx,1));
				$movesArray[$i]['piece'] = substr($thatBoard,$fromIdx,1);
			}


			// check for all other captures (skip if we already have an en passant capture)
			if (! isset($captIdx) && '0' != substr($thatBoard,$toIdx,1))
			{
				$captIdx = $toIdx;
				$captPiece = substr($thatBoard,$toIdx,1);
			}
		}

		// test for checks or mates
#   echo __FILE__ . ' : ' . __LINE__ . '<br />';
		$chess->init_gamestate($FENarray[$i]);
		$state = $chess->get_status_string($thisFEN[1]); // get the current game state

		if ('' != $state)
		{
			$movesArray[$i]['check'] = strtolower($state);
		}

		// put all data into the array
		idx2sqr($fromIdx,$movesArray[$i]['fromSq']);
		$movesArray[$i]['fromRow'] = floor($movesArray[$i]['fromSq'] / 8);
		$movesArray[$i]['fromCol'] = $movesArray[$i]['fromSq'] % 8;
		idx2sqr($toIdx,$movesArray[$i]['toSq']);
		$movesArray[$i]['toRow']   = floor($movesArray[$i]['toSq'] / 8);
		$movesArray[$i]['toCol']   = $movesArray[$i]['toSq'] % 8;

		if (isset($captIdx))
		{
			idx2sqr($captIdx,$movesArray[$i]['captSq']);
			$movesArray[$i]['captRow']   = floor($movesArray[$i]['captSq'] / 8);
			$movesArray[$i]['captCol']   = $movesArray[$i]['captSq'] % 8;
			$movesArray[$i]['captPiece'] = $captPiece;
		}
	}
}


function expandFEN($FEN)
{
	$zeros = array('','0','00','000','0000','00000','000000','0000000','00000000');
	$theFEN = '';

	if (strpos($FEN, ' '))
		$FEN = substr($FEN, 0, strpos($FEN, ' ') );

	for ($i = 0; $i < strlen($FEN); $i++)
	{
		if (substr($FEN,$i,1) > 0 && substr($FEN,$i,1) < 9)
			$theFEN .= $zeros[substr($FEN,$i,1)];
		else
			$theFEN .= substr($FEN,$i,1);
	}

	$theFEN = str_replace('/','',$theFEN); // Leave only pieces and empty squares
	return $theFEN;
}


function packFEN($xFEN)
{
	// insert the row markers
	$xFEN = substr($xFEN, 0,8) . '/' . substr($xFEN, 8,8) . '/'
				. substr($xFEN,16,8) . '/' . substr($xFEN,24,8) . '/'
				. substr($xFEN,32,8) . '/' . substr($xFEN,40,8) . '/'
				. substr($xFEN,48,8) . '/' . substr($xFEN,56,8);

	// compact the FEN
	$count = 0;
	$FENpack = "";
	for ($i = 0; $i < strlen($xFEN); $i++)
	{
		$c = substr($xFEN,$i,1);

		// if we have an empty space...
		if ('0' == $c)
			$count++; // count it

		// or if we have not reached any empty spaces yet...
		elseif (0 == $count)
			$FENpack .= $c; // append the character to the end of the FEN

		// or if we have counted empty spaces then reach the end of them
		elseif (0 != $count && '0' != $c)
		{
			$FENpack .= $count . $c; // append the count number and the next character to the FEN
			$count = 0; // and reset the count var
		}
	}

	// attach the last count number to the FEN if needed
	if ($count)
		$FENpack .= $count;

	return $FENpack;
}


// this function updates all the FEN bits with the POST move data
// and returns the full FEN to be placed in the ".T_HISTORY." table
function movetoFEN( )
{
	global $FENarray,$movesArray,$board,$COLS,$initpos,$pieceColor;

	$num_moves = count($FENarray) - 1;

	// get the post info out
	foreach ($_POST as $key => $var)
		$$key = $var;

	// reverse row and col so i don't confuse myself
	$colFrom = $fromCol;$colTo = $toCol;
	$rowFrom = $fromRow;$rowTo = $toRow;

	// and convert it to something we can use
	colrow2idx($colFrom,$rowFrom,$idxFrom);
	colrow2idx($colTo,$rowTo,$idxTo);

	// get the current FEN data
	$FENitems = explode(' ',$FENarray[$num_moves]);
	$thatFEN = expandFEN($FENitems[0]);
	$CM = $FENitems[1];
	$CI = $FENitems[2];
	$EP = $FENitems[3];
	$PN = $FENitems[4];
	$MN = $FENitems[5];
	$newEP = '-';

	// get original placement of rooks
	$origARookPos = strpos($initpos,'R');
	$origHRookPos = strrpos($initpos,'R');
	$origKingPos  = strpos($initpos,'K');

	// separate the castle indicator
	$WK = (false !== strpos($CI,'K')) ? 'K' : '';
	$WQ = (false !== strpos($CI,'Q')) ? 'Q' : '';
	$BK = (false !== strpos($CI,'k')) ? 'k' : '';
	$BQ = (false !== strpos($CI,'q')) ? 'q' : '';

	// put board into expanded FEN string
	$xFEN = "";
	for ($i = 7; $i >= 0; $i--)
	{
		for ($j = 0; $j < 8; $j++)
		{
			$xFEN .= $board[$i][$j];
		}
	}

	// get the piece that is moving
	$piece = FENplace($xFEN,$idxFrom);

	// check for castling move
	if ('false' != $_POST['castleMove'])
	{
		if ('white' == $pieceColor[$piece])
		{
			// clear the castle indicators
			$WK = '';
			$WQ = '';

			// make the move
			if ('a' == $_POST['castleMove'])
			{
				FENplace($xFEN,$origKingPos + 56,'0'); // delete the king
				FENplace($xFEN,$origARookPos + 56,'0'); // delete the rook
				FENplace($xFEN,2 + 56,'K'); // place the king
				FENplace($xFEN,3 + 56,'R'); // place the rook
			}
			elseif ('h' == $_POST['castleMove'])
			{
				FENplace($xFEN,$origKingPos + 56,'0'); // delete the king
				FENplace($xFEN,$origHRookPos + 56,'0'); // delete the rook
				FENplace($xFEN,6 + 56,'K'); // place the king
				FENplace($xFEN,5 + 56,'R'); // place the rook
			}
			else
			{
				die("castleMove is incorrect");
			}
		}
		elseif ('black' == $pieceColor[$piece]) // black
		{
			// clear the castle indicators
			$BK = '';
			$BQ = '';

			// make the move
			if ('a' == $_POST['castleMove'])
			{
				FENplace($xFEN,$origKingPos,'0'); // delete the king
				FENplace($xFEN,$origARookPos,'0'); // delete the rook
				FENplace($xFEN,2,'k'); // place the king
				FENplace($xFEN,3,'r'); // place the rook
			}
			elseif ('h' == $_POST['castleMove'])
			{
				FENplace($xFEN,$origKingPos,'0'); // delete the king
				FENplace($xFEN,$origHRookPos,'0'); // delete the rook
				FENplace($xFEN,6,'k'); // place the king
				FENplace($xFEN,5,'r'); // place the rook
			}
			else
			{
				die("castleMove is incorrect");
			}
		}
		else
		{
			echo "<pre>";
			for ($i = 0; $i < $idxFrom; $i++)
				echo " ";

			echo "|\n";
			echo $xFEN."</pre>";
		}
	}
	else // or regular moves
	{
		// make the move
		$piece = FENplace($xFEN,$idxFrom,'0');
		$capt = FENplace($xFEN,$idxTo,$piece);
		$PN++;

		// if we have a pawn advance, or a capture
		if ('P' == strtoupper($piece) || '0' != $capt)
			$PN = 0; // reset the ply count

		// if we have a pawn double advance
		if ('P' == strtoupper($piece) && 2 == abs($rowFrom - $rowTo))
			colrow2til($colTo,($rowFrom + $rowTo) * 0.5,$newEP); // set the en passant indicator

		// if we moved a castling piece
		if ('K' == $piece) // white king moved
		{
			$WK = '';$WQ = '';
		}
		elseif ('k' == $piece) // black king moved
		{
			$BK = '';$BQ = '';
		}
		elseif ('R' == $piece) // white rook moved
		{
			if ($colFrom == $origARookPos) // a-side moved
				$WQ = '';
			elseif ($colFrom == $origHRookPos) // h-side moved
				$WK = '';
		}
		elseif ('r' == $piece) // black rook moved
		{
			if ($colFrom == $origARookPos) // a-side moved
				$BQ = '';
			elseif ($colFrom == $origHRookPos) // h-side moved
				$BK = '';
		}
	}

	// check for en passant capture
	colrow2til($colTo,$rowTo,$tilTo);
	if ($tilTo == $EP && 'P' == strtoupper($piece))
	{
		// get the idx of the captured pawn
		colrow2idx($colTo,$rowFrom,$idxCapt);
		// and remove the captured pawn
		FENplace($xFEN,$idxCapt,'0');
	}

	$FENbit = packFEN($xFEN);

	// search for ambiguous castle notation
	//--------------------------------------------
	// remove any extra information from the current castle notations
	if ('' != $WK) $WK = 'K';
	if ('' != $WQ) $WQ = 'Q';
	if ('' != $BK) $BK = 'k';
	if ('' != $BQ) $BQ = 'q';

	// get current position of main pieces
	$whiteBackRank = substr($xFEN,-8);
	$blackBackRank = substr($xFEN,0,8);

	// search the ends of the back ranks for rooks
	// and add unambiguous notation if needed
	if (strrpos($whiteBackRank,'R') > $origHRookPos && '' != $WK) $WK = $WK . substr($COLS,$origHRookPos,1);
	if (strpos($whiteBackRank,'R')  < $origARookPos && '' != $WQ) $WQ = $WQ . substr($COLS,$origARookPos,1);
	if (strrpos($blackBackRank,'r') > $origHRookPos && '' != $BK) $BK = $BK . substr($COLS,$origHRookPos,1);
	if (strpos($blackBackRank,'r')  < $origARookPos && '' != $BQ) $BQ = $BQ . substr($COLS,$origARookPos,1);

	$castlingAvail = $WK . $WQ . $BK . $BQ;

	if ('' == $castlingAvail)
		$castlingAvail = '-';

	// increase the move number (if needed)
	$MN = ("w" == $CM) ? $MN : ++$MN; // make sure to use the pre-increment (++var) here

	// toggle the current move
	$CM = ("w" == $CM) ? "b" : "w";

	// put the whole thing together and return
	return "$FENbit $CM $castlingAvail $newEP $PN $MN";
}


// this function takes an expanded FEN 'xFEN'
// and replaces whatever is at index 'idx'
// with 'item', no questions asked
// returns FEN as reference, and original item as return
function FENplace(&$xFEN, $idx, $item = 'NONE')
{
	// get the original item
	$orig = substr($xFEN,$idx,1);

	if ("NONE" !== $item) // if there is an item
	{
		$xFEN = substr($xFEN,0,$idx) . $item . substr($xFEN,$idx + 1); // replace it
	}

	// if there is no item, we just want to know what is at pos
	// so do nothing to $xFEN, just return the object that is there

	return $orig;
}


//******************************************************************************
//  CONVERSION FUNCTIONS
//******************************************************************************

// the format for items is to have the location first, then the type
// ie: from_sqr, to_idx, capt_row, etc.

// these functions convert from one format to another
// row - a numerical representation of the rank. row 0-7 = rank 1-8
// col - same as above for files. col 0-7 = file a-h
// til - a PGN notated representation of the square. ie a3, f6, etc. (PGN tile)
// idx - a numerical representation of a location in FEN space. (FEN index)
//       ie 0-63 for an expanded FEN, 0 = a8, 7 = h8, 56 = a1, and 63 = h1
// sqr - a numerical representation of a location in board space. (board square)
//       ie 0-63 for a board, 0 = a1, 7 = a8, 56 = h1, and 63 = h8

// all the functions return values by way of references and standard return when
// only one value is returned, function with two return values use references
// explicitly.  the name of the function also gives the argument order:
// ie: sqr2colrow(sqr,col,row)

// this function takes an idx value
// and returns a sqr value by reference
function idx2sqr($idx,&$sqr)
{
	if (0 > $idx || 63 < $idx)
		die('idx not in [0-63]: ' . $idx);

	$sqr = (8 * (7 - floor($idx * 0.125))) + ($idx % 8);

	return $sqr;
}

function sqr2idx($sqr,&$idx)
{
	if (0 > $sqr || 63 < $sqr)
		die('sqr not in [0-63]: ' . $sqr);

	$idx = (8 * (7 - floor($sqr * 0.125))) + ($sqr % 8);

	return $idx;
}


// this function takes a row and col in 0-7
// format and converts it to a sqr
function colrow2sqr($col,$row,&$sqr)
{
	if (0 > $row || 7 < $row)
		die('row not in [0-7]: ' . $row);
	if (0 > $col || 7 < $col)
		die('col not in [0-7]: ' . $col);

	$sqr = ($row * 8) + $col;

	return $sqr;
}

function sqr2colrow($sqr,&$col,&$row)
{
	if (0 > $sqr || 63 < $sqr)
		die('sqr not in [0-63]: ' . $sqr);

	$col = ($sqr % 8);
	$row = floor($sqr * 0.125);
}


// this function takes a til value
// and returns the row and col
function til2colrow($til,&$col,&$row)
{
	global $COLS;

	if (2 != strlen($til))
		die('til not two characters long: ' . $til);

	$row = intval(substr($til,1,1)) - 1;
	$col = strpos($COLS,substr($til,0,1));
}

function colrow2til($col,$row,&$til)
{
	global $COLS;

	if (0 > $row || 7 < $row)
		die('row not in 0-7: ' . $row);
	if (0 > $col || 7 < $col)
		die('col not in 0-7: ' . $col);

	$rank = $row + 1;
	$file = substr($COLS,$col,1);

	$til = $file . $rank . '';

	return $til;
}


// this function takes row and col values
// and converts it to an idx
function colrow2idx($col,$row,&$idx)
{
	if (0 > $row || 7 < $row)
		die('row not in [0-7]: ' . $row);
	if (0 > $col || 7 < $col)
		die('col not in [0-7]: ' . $col);

	colrow2sqr($col,$row,$sqr);
	sqr2idx($sqr,$idx);

	return $idx;
}

function idx2colrow($idx,&$col,&$row)
{
	if (0 > $idx || 63 < $idx)
		die('idx not in [0-63]: ' . $idx);

	idx2sqr($idx,$sqr);
	sqr2colrow($sqr,$col,$row);
}


// this function takes a til value
// and converts it to an idx
function til2idx($til,&$idx)
{
	if (2 != strlen($til))
		die('til not two characters long: ' . $til);

	til2colrow($til,$col,$row);
	colrow2sqr($col,$row,$sqr);
	sqr2idx($sqr,$idx);

	return $idx;
}

function idx2til($idx,&$til)
{
	if (0 > $idx || 63 < $idx)
		die('idx not in [0-63]: ' . $idx);

	idx2sqr($idx,$sqr);
	sqr2colrow($sqr,$col,$row);
	colrow2til($col,$row,$til);

	return $til;
}


// this function takes a til value
// and converts it to a sqr
function til2sqr($til,&$sqr)
{
	if (2 != strlen($til))
		die('til not two characters long: ' . $til);

	til2colrow($til,$col,$row);
	colrow2sqr($col,$row,$sqr);

	return $sqr;
}

function sqr2til($sqr,&$til)
{
	if (0 > $sqr || 63 < $sqr)
		die('sqr not in [0-63]: ' . $sqr);

	sqr2colrow($sqr,$col,$row);
	colrow2til($col,$row,$til);

	return $til;
}

//*** END CONVERTERS ***********************************************************

