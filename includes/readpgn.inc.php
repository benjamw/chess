<?php

// this script reads in a PGN file and saves the contents to
// the vars needed to run the WebChess watchgame script.
// it makes no validity checks, it only moves the pieces.
// you should make sure your PGN file is a valid game file
// before running it on this script. (although it doesn't
// necessarily need to be to work)

// row - a numerical representation of the rank. row 0-7 = rank 1-8
// col - same as above for files. col 0-7 = file a-h
// til - a PGN notated representation of the square. ie a3, f6, etc. (PGN tile)
// idx - a numerical representation of a location in FEN space. (FEN index)
//       ie 0-63 for an expanded FEN, 0 = a8, 7 = h8, 56 = a1, and 63 = h1
// sqr - a numerical representation of a location in board space. (board square)
//       ie 0-63 for a board, 0 = a1, 7 = a8, 56 = h1, and 63 = h8

// conversion arrays
$cols = 'abcdefgh';

// leave this as false, it has a switch below
$DEBUG = false;

//* DEBUGGINg (test for incoming file, if none, output to user with default file) -----
if ( ! isset($pgnReadFile) )
{
	$DEBUG = true;
	ini_set("display_errors",'On');
	error_reporting(E_ALL);
	$pgnReadFile = "../pgn/castle_test03.pgn";
}
//*/

$pgn = file_get_contents($pgnReadFile);

// remove all extraneous info from the PGN file
$comments = array (
										"|^%[^\\r\\n]*|m", // full line comments
										"|\\{[^}]*\\}|",   // block comments (may contain parens and line breaks)
										"|\\(.+\\)|",      // alternate moves
										"|;[^\\r\\n]*|",   // "rest of line" comments
										"|\\$\d+|",        // move annotations
										"|!+|",            // move annotations
										"|\\?+|"           // move annotations
									);
$pgn = preg_replace($comments,'',$pgn);

// replace all multiple spaces and new lines with single spaces
$pgn = preg_replace('|\\s+|',' ',$pgn);

// extract out the important 'meta' information
if(preg_match('|\\[White "([^"\\r\\n]*)"\\]|',$pgn,$match))
	$white = $match[1];

if(preg_match('|\\[Black "([^"\\r\\n]*)"\\]|',$pgn,$match))
	$black = $match[1];

if(preg_match('|\\[Result "([^"\\r\\n]*)"\\]|',$pgn,$match))
	$gameResult = $match[1];

if(preg_match('|\\[FEN "([^"\\r\\n]*)"\\]|',$pgn,$match))
	$FENarray[0] = $match[1];
else // if no FEN was supplied, use the default FEN
	$FENarray[0] = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";

$initpos = strtoupper(substr($FENarray[0],0,8));
$id960   = getId960($initpos);

$origKingPos  = strpos($initpos,'K');
$origARookPos = strpos($initpos,'R');
$origHRookPos = strrpos($initpos,'R');

// erase all the 'meta' data from the string
$pgn = trim( substr($pgn, strrpos($pgn, "]") + 1) );

$pgn = $pgn;

// expand the FEN
$ones = array('','1','11','111','1111','11111','111111','1111111','11111111');
$fEN = trim( substr($FENarray[0], 0, strpos($FENarray[0], ' ') ) );
$fEN = str_replace('/','',$fEN);
$theFEN = '';

for ($i = 0; $i < strlen($fEN); $i++)
{
	if (substr($fEN,$i,1) > '1' && substr($fEN,$i,1) < '9')
		$theFEN .= $ones[substr($fEN,$i,1)];
	else
		$theFEN = $theFEN . substr($fEN,$i,1);
}

$fEN = $theFEN;

// initialize a movable board from the FEN ----
// this board is used to keep track of where
// the pieces are during the game so we can
// determine where the piece came from
//
// $board[row][col] = piece letter at row col, 1 if none
for ($i = 7; $i >= 0; $i--)
{
	for ($j = 0; $j < 8; $j++)
	{
		$board[$i][$j] = substr($fEN,0,1);
		$fEN = substr($fEN,1);
	}
}

if ($DEBUG) {
	printboard( );
	board2FEN($board,$fEN = '');
	echo "<pre>$fEN w KQkq - 0 1</pre>";
	echo "<hr />";
}

// now that we have everything, start reading the moves into the FEN array
// -----------------------------------------------------------------------------
// init all the FEN notation vars
// CI = color indicator
// WK - BQ = castle indicators
// EP = en passant target indicator
// PN = ply number since last pawn advance or piece capture
// MN = whole move number
$CI = "w";$WK = "K";
$WQ = "Q";$BK = "k";
$BQ = "q";$EP = "-";
$PN = "0";$MN = "1";

$color = 'b'; // init the color var (will switch to white after first move)
$int = 0;
$numMoves = -1; // move counter (it counts one too many and breaks the script if set to 0)

while ('DONE' != $pgn)
{
	if (0 != $int % 2) // on every other move
		$MN++; // increment the move number

	$int++; // increment the counting var
	$PN++;  // increment the ply number
	$CI = ('w' == $CI) ? 'b' : 'w'; // toggle the color indicator
	$move = get_next_move( ); // get the next move
	unset($ambFrom); // reset the ambiguous move indicator
	$EP = '-'; // reset the en passant indicator

	if ($DEBUG) echo "<pre>$move</pre>";

	// remove any check or checkmate marks while getting
	// the next move (they are not needed)
	$move = preg_replace('|[+#]|','',$move);
	$piece = substr($move,0,1);
	unset($tilFrom);

	// if we are at the result
	if (preg_match('|^[*01]|',$piece))
		break; // break out of the loop

	if (preg_match('|[KQRBN]|',$piece))
	{
		$move = substr($move,1);

		// if we need to look for multiple pieces or captures
		if (2 < strlen($move))
		{
			// for captures
			if ('x' == substr($move,0,1))
			{
				$move = substr($move,1); // just replace position as normal
				$PN = 0; // reset the ply number
			}
			else // two possible moves ?
			{
				$ambFrom = substr($move,0,1);
				$move = substr($move,1);

				// still capturing ?
				if ('x' == substr($move,0,1))
				{
					$move = substr($move,1); // same as above
					$PN = 0; // reset the ply number
				}
			}
		}

		switch ($piece)
		{
			case 'K':
				$m = array(array(1,1,1,0,-1,-1,-1,0),array(-1,0,1,1,1,0,-1,-1)); // movement array
				$piece = ('w' == $color) ? 'K' : 'k';
				findPieces($move,$m,$piece,'');
				break;
		// --------------------------------------------
			case 'Q':
				$m = array(array(1,1,1,0,-1,-1,-1,0),array(-1,0,1,1,1,0,-1,-1)); // movement array
				$piece = ('w' == $color) ? 'Q' : 'q';
				findPieces($move,$m,$piece,'');
				break;
		// --------------------------------------------
			case 'R':
				$m = array(array(1,0,-1,0),array(0,1,0,-1)); // movement array
				$piece = ('w' == $color) ? 'R' : 'r';
				$ambFrom = isset($ambFrom) ? $ambFrom : '';
				findPieces($move,$m,$piece,$ambFrom);
				break;
		// --------------------------------------------
			case 'B':
				$m = array(array(1,1,-1,-1),array(1,-1,1,-1)); // movement array
				$piece = ('w' == $color) ? 'B' : 'b';
				$ambFrom = isset($ambFrom) ? $ambFrom : '';
				findPieces($move,$m,$piece,$ambFrom);
				break;
		// --------------------------------------------
			case 'N':
				$ambFrom = isset($ambFrom) ? $ambFrom : '';
				findKnights($move,$ambFrom);
				break;
		// --------------------------------------------
			default:
				die("Error in switch");
				break;
		}

		// save the current FEN to the FEN array
		$fEN = '';
		board2FEN($board, $fEN);
		$FENarray[$int] = "$fEN $CI ".getCastle( )." $EP $PN $MN";

		if ($DEBUG) {
			printboard( );
			echo "<pre>$fEN $CI ".getCastle( )." $EP $PN $MN</pre>";
			echo "<hr />";
		}
	}
	else if ('O' == $piece) // castling move
	{
		// set the vars based on color
		if ('w' == $color) // white move
		{
			$K = 'K';
			$R = 'R';
			$ROW = 0;
			$WK = '';
			$WQ = '';
		}
		else // black move
		{
			$K = 'k';
			$R = 'r';
			$ROW = 7;
			$BK = '';
			$BQ = '';
		}

		// remove the king
		if ($K != $board[$ROW][$origKingPos]) die("Castle King not found at $ROW,$origKingPos");
		$board[$ROW][$origKingPos] = '1';

		if ('O-O' == $move)
		{ // Castle Kingside (h-side)
			// place the king
			$test = $board[$ROW][6];
			if ('1' != $test && $R != $test) die("Castle, piece in the way at $ROW,6");
			$board[$ROW][6] = $K;

			// remove the H rook
			if ($R != $board[$ROW][$origHRookPos] && 6 != $origHRookPos) die("Castle H Rook not found at $ROW,$origHRookPos");

			if (6 != $origHRookPos) // if so, the king is already there
				$board[$ROW][$origHRookPos] = '1';

			// place the H rook
			if ('1' != $board[$ROW][5]) die("Castle, piece in the way at $ROW,5");
			$board[$ROW][5] = $R;
		}
		else // O-O-O
		{ // Castle Queenside (a-side)
			// place the king
			$test = $board[$ROW][2];
			if ('1' != $test && $R != $test) die("Castle, piece in the way at $ROW,2");
			$board[$ROW][2] = $K;

			// remove the A rook
			if ($R != $board[$ROW][$origARookPos] && 2 != $origARookPos) die("Castle A Rook not found at $ROW,$origARookPos");

			if (2 != $origARookPos) // if so, the king is already there
				$board[$ROW][$origARookPos] = '1';

			// place the A rook
			if ('1' != $board[$ROW][3]) die("Castle, piece in the way at $ROW,3");
			$board[$ROW][3] = $R;
		}

		// save the current FEN to the FEN array
		$fEN = '';
		board2FEN($board, $fEN);
		$FENarray[$int] = "$fEN $CI ".getCastle( )." $EP $PN $MN";

		if ($DEBUG) {
			printboard( );
			echo "<pre>$fEN $CI ".getCastle( )." $EP $PN $MN</pre>";
			echo "<hr />";
		}
	}
	else // pawn move
	{
		if (false !== strpos($move,'x'))
		{
			$ambFrom = substr($move,0,1);
			$move = substr($move,2);
		}

		$tilTo = substr($move,0,2);

		til2rowcol($tilTo,$rowTo,$colTo);

		if (isset($ambFrom) && '' != $ambFrom) // capture
		{
			if ('w' == $color)
				$tilFrom = $ambFrom . $rowTo;
			else
				$tilFrom = $ambFrom . ($rowTo + 2);

			til2rowcol($tilFrom,$rowFrom,$colFrom);
		}
		else
		{
			$colFrom = $colTo;

			if ('w' == $color)
			{
				if ('P' == $board[$rowTo - 1][$colTo])
					$rowFrom = $rowTo - 1;
				else
					$rowFrom = $rowTo - 2;
			}
			else
			{
				if ('p' == $board[$rowTo + 1][$colTo])
					$rowFrom = $rowTo + 1;
				else
					$rowFrom = $rowTo + 2;
			}
		}

		$trsfr = $board[$rowFrom][$colFrom];

		if ('1' == $trsfr) die("pawn missing at {$rowFrom},{$colFrom}");

		if ($colFrom != $colTo && '1' == $board[$rowTo][$colTo])
		{ // pawn capture, en passant move
			// the piece started on a square next to the captured piece
			// so the row is the same as the fromRow, and because the
			// capturing piece moves behind the captured piece, the col
			// is the same as the toCol.  works for both white and black
			$board[$rowFrom][$colTo] = '1';
		}

		// move the pawn
		$board[$rowFrom][$colFrom] = '1';
		$board[$rowTo][$colTo] = $trsfr;


		if (false !== strpos($move,'='))
		{
			$proTo = substr($move,-1);

			if ('b' == $color) $proTo = strtolower($proTo);

			$board[$rowTo][$colTo] = $proTo;
		}

		// change en passant indicator if needed
		if (2 == abs($rowTo - $rowFrom))
		{
			if ('w' == $color)
				$EP = substr($cols,$colFrom,1) . '3';
			else
				$EP = substr($cols,$colFrom,1) . '6';
		}

		$PN = 0; // reset the ply number

		// save the current FEN to the FEN array
		$fEN = '';
		board2FEN($board, $fEN);
		$FENarray[$int] = "$fEN $CI ".getCastle( )." $EP $PN $MN";

		if ($DEBUG) {
			printboard( );
			echo "<pre>$fEN $CI ".getCastle( )." $EP $PN $MN</pre>";
			echo "<hr />";
		}
	}
} // end history while loop

if ($DEBUG)
{
	echo "<pre>\n";
	print_r($FENarray);
	echo "</pre>";
}


//------------------------------------------------------------------
// EXTRA FUNCTIONs
//------------------------------------------------------------------

// a function to return the mext move in the pgn and then step it to the next one.
function get_next_move( )
{
	global $pgn,$color,$numMoves;

	// if it's white's move, skip an item (the move number)
	if ('b' == $color)
	{
		$color = 'w'; // change the move var

		if (false === strpos(substr($pgn,0,5),'.')) // if there is no period in the next 5 characters
			$pos = strpos($pgn,' '); // remove to the next space
		else // otherwise, there is a period (and may not be a space between move number and move)
			$pos = strpos($pgn,'.'); // remove to next period

		$pgn = trim( substr($pgn,$pos + 1) ); // make sure to trim, in case of period removal
	}
	else // it was black's move, just change the move var
		$color = 'b';

	// get the next item
	$pos = strpos($pgn,' ');

	if (false === $pos || "" == $pgn)
	{ // if there are no more spaces, quit the while loop
		$result = $pgn;
		$pgn = 'DONE';
		return '**DONE**';
	}

	$move = substr($pgn,0,$pos);
	$pgn = trim( substr($pgn,$pos) );

	$numMoves++;

	return $move;
}

// this function takes a pgn tile (ie f3, g7, etc) and returns
// (by way of referenced arguments) the row and col in 0-7 format
// row 0-7 -> rank 1-8,  col 0-7 -> file a-h
// ie f3 -> row=2, col=5;  a1 -> r=0, c=0;  h8 -> r=7, c=7
function til2rowcol($til,&$row,&$col)
{
	global $cols;

	if (2 != strlen($til))
		die('til not two characters long: ' . $til);

	$row = intval(substr($til,1,1)) - 1;
	$col = strpos($cols,substr($til,0,1));
}

// this function reverses the above function
function rowcol2til($row,$col,&$til)
{
	global $cols;

	if (0 > $row || 7 < $row)
		die('Row not in 0-7: ' . $row);
	if (0 > $col || 7 < $col)
		die('Col not in 0-7: ' . $col);

	$rank = $row + 1;
	$file = substr($cols,$col,1);

	$til = $file . $rank . '';
}


// find all knights that can move to $tilTo
// $tilTo given as pgn square (a7, h4, etc.)
// no output given, function changes board
// and history vars.
function findKnights($tilTo,$ambFrom)
{
	global $color,$cols,$board;

	til2rowcol($tilTo,$rowTo,$colTo);

	if ('w' == $color) $KNIGHT = 'N';
	else               $KNIGHT = 'n';

	$m = array(array(2,2,1,-1,-2,-2,-1,1),array(-1,1,2,2,1,-1,-2,-2)); // movement array

	for ($j = 0; $j < 8; $j++)
	{
		$row = $rowTo + ($m[0][$j]);
		$col = $colTo + ($m[1][$j]);

		if (0 > $row || 0 > $col || 7 < $row || 7 < $col)
			continue; // do not break, there are more squares to check, just skip

		if ($KNIGHT == $board[$row][$col])
		{
			rowcol2til($row,$col,$tilFrom);
			$found[] = $tilFrom;
		}
		// no need to look for blocking pieces, knights jump
	}

	if ('' != $ambFrom) // do we have an unambiguous move indicator?
	{
		for ($i = 0; $i < 2; $i++)
		{
			if (false !== strpos($found[$i],$ambFrom))
			{
				til2rowcol($found[$i],$rowFrom,$colFrom);
				break;
			}
		}
	}
	else if (1 < count($found))
		die("Ambiguous Knight move found");
	else
		til2rowcol($found[0],$rowFrom,$colFrom);

	// get TO square
	til2rowcol($tilTo,$rowTo,$colTo);

	// remove knight from FROM square
	$board[$rowFrom][$colFrom] = '1';

	// place knight in TO square
	$board[$rowTo][$colTo] = $KNIGHT;
}


// find all pieces that can move to $tilTo
// $tilTo given as pgn square (a7, h4, etc.)
// no output given, function changes board
// and history vars.
function findPieces($tilTo,$m,$PIECE,$ambFrom)
{
	global $color,$cols,$board,$origKingPos,$origARookPos,$origHRookPos,$WK,$WQ,$BK,$BQ;

	til2rowcol($tilTo,$rowTo,$colTo);

	if ('K' == strtoupper($PIECE))
	{
		$k = 2; // only check one square away for kings

		// remove castle indicators since the king moved
		if ('w' == $color) { $WK = ''; $WQ = ''; }
		else               { $BK = ''; $BQ = ''; }
	}
	else
		$k = 8; // check all eight squares away for other pieces


	for ($j = 0; $j < count($m[0]); $j++)
	{
		for ($i = 1; $i < $k; $i++) // start adding from 1, not 0
		{
			$row = $rowTo + ($i * $m[0][$j]);
			$col = $colTo + ($i * $m[1][$j]);

			if (0 > $row || 0 > $col || 7 < $row || 7 < $col)
				break; // if we are outside the board area, break

			if ($PIECE == $board[$row][$col]) // the piece was found
			{
				rowcol2til($row,$col,$tilFrom);
				$found[] = $tilFrom;
				break; // searching this path no longer required
			}
			else if ('1' != $board[$row][$col]) // another piece was found and jumping is not allowed
				break; // searching this path no longer required
		}
	}

	$rowFrom = $colFrom = 0;
	if ('' != $ambFrom) // do we have an unambiguous move indicator?
	{
		for ($i = 0; $i < 2; $i++)
		{
			if (false !== strpos($found[$i],$ambFrom))
			{
				til2rowcol($found[$i],$rowFrom,$colFrom);
				break;
			}
		}
	}
	else if (1 < count($found))
		die("Ambiguous {$PIECE} move found");
	else
		til2rowcol($found[0],$rowFrom,$colFrom);

	// get TO square
	til2rowcol($tilTo,$rowTo,$colTo);

	// remove castle indicators if needed
	if ('R' == strtoupper($PIECE)) // if the piece is a rook
	{
		if ('w' == $color) { $ROW = 0; $var = 'W'; }
		else               { $ROW = 7; $var = 'B'; }

		if ($ROW == $rowFrom && $colFrom == $origARookPos) // and it moved from the original location
		{
			$var = $var . 'Q';
			$$var = ''; // remove the castle indicator
		}
		else if ($ROW == $rowFrom && $colFrom == $origHRookPos)
		{
			$var = $var . 'K';
			$$var = '';
		}
	}

	// remove piece from FROM square
	$board[$rowFrom][$colFrom] = '1';

	// place piece in TO square
	$board[$rowTo][$colTo] = $PIECE;
}


// this function converts the current board to
// the board portion of the FEN string
function board2FEN($board, &$FENbit)
{
	// put board into expanded FEN string
	$xFEN = "";
	for ($i = 7; $i >= 0; $i--)
	{
		for ($j = 0; $j < 8; $j++)
		{
			$xFEN .= $board[$i][$j];
		}
	}

	// insert the row markers
	$xFEN = substr($xFEN, 0,8) . '/' . substr($xFEN, 8,8) . '/'
				. substr($xFEN,16,8) . '/' . substr($xFEN,24,8) . '/'
				. substr($xFEN,32,8) . '/' . substr($xFEN,40,8) . '/'
				. substr($xFEN,48,8) . '/' . substr($xFEN,56,8);

	// compact the empty squares
	$count = 0;
	for ($i = 0; $i < strlen($xFEN); $i++)
	{
		$c = substr($xFEN,$i,1);

		// if we have an empty space...
		if ('1' == $c)
			$count++; // count it

		// or if we have not reached any empty spaces yet...
		else if (0 == $count)
			$FENbit .= $c; // append the character to the end of the FEN

		// or if we have counted empty spaces then reach the end of them
		else if (0 != $count && '1' != $c)
		{
			$FENbit .= $count . $c; // append the count number and the next character to the FEN
			$count = 0; // and reset the count var
		}
	}

	// attach the last count number to the FEN if needed
	if ($count)
		$FENbit .= $count;
}


// this function searches for any ambiguous castle notations and fixes them
// returning the entire castle indicator, empty or not
function getCastle( )
{
	global $board,$WK,$WQ,$BK,$BQ,$origARookPos,$origHRookPos,$cols;

	// search for ambiguous castle notation
	//--------------------------------------------
	// remove any extra information from the current castle notations
	if ('' != $WK) $WK = 'K';
	if ('' != $WQ) $WQ = 'Q';
	if ('' != $BK) $BK = 'k';
	if ('' != $BQ) $BQ = 'q';

	// get position of main pieces
	$whiteBackRank = $board[0][0].$board[0][1].$board[0][2].$board[0][3].$board[0][4].$board[0][5].$board[0][6].$board[0][7];
	$blackBackRank = $board[7][0].$board[7][1].$board[7][2].$board[7][3].$board[7][4].$board[7][5].$board[7][6].$board[7][7];

	// search the ends of the back ranks for rooks
	// and add unambiguous notation if needed
	if (strrpos($whiteBackRank,'R') > $origHRookPos && '' != $WK) $WK = $WK . substr($cols,$origHRookPos,1);
	if (strpos($whiteBackRank,'R')  < $origARookPos && '' != $WQ) $WQ = $WQ . substr($cols,$origARookPos,1);
	if (strrpos($blackBackRank,'r') > $origHRookPos && '' != $BK) $BK = $BK . substr($cols,$origHRookPos,1);
	if (strpos($blackBackRank,'r')  < $origARookPos && '' != $BQ) $BQ = $BQ . substr($cols,$origARookPos,1);

	$castlingAvail = $WK . $WQ . $BK . $BQ;

	if ($castlingAvail == '')
		$castlingAvail = '-';

	return $castlingAvail;
}

// this function takes a position "RNBQKBKR"
// and returns the id960 that creates that position
function getId960($pos)
{
	$B1 = strpos($pos,'B'); // get the location of the bishops
	$B2 = strrpos($pos,'B');

	$pos = str_replace('B','',$pos); // remove the Bs

	$Q = strpos($pos,'Q'); // get the location of the queen

	$pos = str_replace('Q','',$pos); // remove the Q

	switch ($pos)
	{
		case 'NNRKR' : $krn = 0; break;
		case 'NRNKR' : $krn = 1; break;
		case 'NRKNR' : $krn = 2; break;
		case 'NRKRN' : $krn = 3; break;
		case 'RNNKR' : $krn = 4; break;
		case 'RNKNR' : $krn = 5; break;
		case 'RNKRN' : $krn = 6; break;
		case 'RKNNR' : $krn = 7; break;
		case 'RKNRN' : $krn = 8; break;
		case 'RKRNN' : $krn = 9; break;
	}

	if (0 == ($B1 % 2)) // if the bishops are incorrect (B1 != light, B2 != dark)
	{ // reverse them
		$l = $B1;
		$B1 = $B2;
		$B2 = $l;
	}

	$B1 = floor($B1 / 2); // get the true position for light
	$B2 = floor($B2 / 2); // and dark bishops

	return $B1 + (4 * $B2) + (16 * $Q) + (96 * $krn);
}


// debugging function to output the board
function printboard( )
{
	global $board;
	echo "<pre>";
	for ($i = 7; $i >= 0; $i--)
	{
		for ($j = 0; $j < 8; $j++)
		{
			if ('1' === $board[$i][$j])
				echo ". ";
			else
				echo $board[$i][$j] . " ";
		}
		echo "\n";
	}
	echo "</pre>";
}

?>