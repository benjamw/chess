<?php

// define constants
define ("EMPTY",   0); # 0000 0000
define ("PAWN",    1); # 0000 0001
define ("KNIGHT",  2); # 0000 0010
define ("BISHOP",  4); # 0000 0100
define ("ROOK",    8); # 0000 1000
define ("QUEEN",  16); # 0001 0000
define ("KING",   32); # 0010 0000
define ("BLACK", 128); # 1000 0000
define ("WHITE",   0); # 0000 0000
define ("COLOR_MASK", 127); # 0111 1111

// define some 'global' conversion arrays
$colorArray = array(
	'w' => 'white',
	'b' => 'black'
);
// used for converting from a *current* FEN to who made the previous move
$oppColorArray = array(
	'w' => 'black' ,
	'b' => 'white'
);

$pieceColor = array(
	'P' => 'white',
	'N' => 'white',
	'B' => 'white',
	'R' => 'white',
	'Q' => 'white',
	'K' => 'white',
	'p' => 'black',
	'n' => 'black',
	'b' => 'black',
	'r' => 'black',
	'q' => 'black',
	'k' => 'black'
);

$pieceName = array(
	'P' => 'pawn',
	'N' => 'knight',
	'B' => 'bishop',
	'R' => 'rook',
	'Q' => 'queen',
	'K' => 'king',
	'p' => 'pawn',
	'n' => 'knight',
	'b' => 'bishop',
	'r' => 'rook',
	'q' => 'queen',
	'k' => 'king'
);

// $pieceCode[color][pieceName]
$pieceCode = array(
	'white' => array(
		'pawn'   => 'P',
		'knight' => 'N',
		'bishop' => 'B',
		'rook'   => 'R',
		'queen'  => 'Q',
		'king'   => 'K'
	),
	'black' => array(
		'pawn'   => 'p',
		'knight' => 'n',
		'bishop' => 'b',
		'rook'   => 'r',
		'queen'  => 'q',
		'king'   => 'k'
	)
);

// convert from javascript code (binary numbers)
// to piece code
function jsCode2pieceCode($code)
{
	global $pieceCode;

	if ($code & BLACK)
		$color = "black";
	else
		$color = "white";

	$code = $code & COLOR_MASK;

	switch ($code)
	{
		case PAWN:   $piece = 'pawn';   break;
		case KNIGHT: $piece = 'knight'; break;
		case BISHOP: $piece = 'bishop'; break;
		case ROOK:   $piece = 'rook';   break;
		case QUEEN:  $piece = 'queen';  break;
		case KING:   $piece = 'king';   break;
	}

	return $pieceCode[$color][$piece];
}

$COLS = 'abcdefgh';

?>