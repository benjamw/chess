/* these are utility functions used by other functions */

var Files = ['a','b','c','d','e','f','g','h'];

var pieceNameToLtr = {'king':'k','queen':'q','rook':'r','bishop':'b','knight':'n','pawn':'p'};

var pieceLtrToName = {'k':'king','q':'queen','r':'rook','b':'bishop','n':'knight','p':'pawn'};

var pieceColor = {'K':'w','Q':'w','R':'w','B':'w','N':'w','P':'w',
							'k':'b','q':'b','r':'b','b':'b','n':'b','p':'b'};

var colorLtrToName = {'w':'white','b':'black'};


function isInBoard(row,col)
{
		if ( (row >= 0) && (row <= 7) && (col >= 0) && (col <= 7) )
				return true;
		else
				return false;
}


function getPieceColor(piece)
{
		if (BLACK & piece)
				return "black";
		else
				return "white";
}


function getPieceName(piece)
{
		var pieceName = new Array( );
		pieceName[PAWN]   = "pawn";
		pieceName[ROOK]   = "rook";
		pieceName[KNIGHT] = "knight";
		pieceName[BISHOP] = "bishop";
		pieceName[QUEEN]  = "queen";
		pieceName[KING]   = "king";

		return pieceName[piece & COLOR_MASK];
}

function getPieceCode(color, piece)
{
	var code;
	switch(piece)
	{
		case "pawn":
			code = PAWN;
			break;
		case "knight":
			code = KNIGHT;
			break;
		case "bishop":
			code = BISHOP;
			break;
		case "rook":
			code = ROOK;
			break;
		case "queen":
			code = QUEEN;
			break;
		case "king":
			code = KING;
			break;
	}

	if (color == "black")
		code = BLACK | code;

	return code;
}



function getOtherColor(color)
{
	if (color == "white")
		return "black";
	else
		return "white";
}


//
// FEN functions
//
function expandFEN(FEN) {
	var ones = new Array ('', '1' ,'11', '111', '1111', '11111', '111111', '1111111', '11111111');
	var thisFEN = '';
	for(var i=0; i < FEN.length; i++) {
		if(FEN.charAt(i) > '1' && FEN.charAt(i) < '9') {
			thisFEN += (ones[Number(FEN.charAt(i))]);
		} else {
			thisFEN = thisFEN + '' + FEN.charAt(i);
		}
	}
	return thisFEN.replace(/\//g,"");  // Leave only pieces and empty squares
}

function setSquare(Square,Piece) {
	var rank = 7 - parseInt(Square / 8);
	var file = Square % 8;
	var s;

	if (Piece == '1')
		s = 0;
	else
		s = getPieceCode(colorLtrToName[pieceColor[Piece]], pieceLtrToName[Piece.toLowerCase( )]);

	board[rank][file] = s;
}

function FENToBoard(FEN) {
	var FENItems = new Array( );
	FENItems = FEN.split(' ');
	var xFEN = expandFEN(FENItems[0]);
	var c;
	for(var i = 0; i < 64; i++) {
		c = xFEN.charAt(i);
		setSquare(i,c);
	}
	var curColor = colorLtrToName[FENItems[1]];
};


function FENToCapt(idx)
{
	var i;
	var thisMove;
	var captW = new Array( );
	var captB = new Array( );
	var a = 0;
	var b = 0;

	// collect the captures
	for (i = idx; i > 0; i--) // stop before 0 because at 0 there is no previous move
	{
		thisMove = new previousMove(i);

		if (thisMove.captPiece)
		{
			if ('w' == pieceColor[thisMove.captPiece])
			{
				captW[a] = pieceLtrToName[thisMove.captPiece.toLowerCase( )];
				a++;
			}
			else
			{
				captB[b] = pieceLtrToName[thisMove.captPiece.toLowerCase( )];
				b++;
			}
		}
	}

	// sort the captures
	captW.sort(pieceCompare);
	captB.sort(pieceCompare);

	captPieces = [captW,captB];
}


function pieceCompare(a,b)
{
	var aV = setValue(a);
	var bV = setValue(b);

	if (aV > bV) return -1;
	if (aV < bV) return 1;
	return 0;
}


function setValue(piece)
{
	var val;
	if ("knight" == piece) piece = "night"; // remove the leading 'K'
	var p = piece.charAt(0).toUpperCase( );

	if ('K' == p)
		val = 100;
	else if ('Q' == p)
		val = 9;
	else if ('R' == p)
		val = 5;
	else if ('B' == p)
		val = 4;
	else if ('N' == p)
		val = 3;
	else if ('P' == p)
		val = 1;
	else
		val = 0;

	return val;
}



function packFEN(piecePlacement,activeColor,castlingAvail,epSquare,halfmoveClock,fullmoveNumber)
{ // Pack all the FEN fields into one string
	var FEN = '';
	var idx = 0;
	var empty = 0;
	var c = '';
	for(var i = 0; i < 64; i++)
	{ // Generate the correct piece placement string
		if(i > 0 && (i % 8 == 0))
		{ // New row
			if(empty > 0)
			{ // Count of empty squares does not continue across rows
				FEN += empty + "";
				empty = 0;
				idx++;
			}
			FEN += '/'; // New row
		}
		c = piecePlacement.charAt(i);
		if(c == '1')
		{ // Count consecutive empty squares
			empty++;
		}
		else
		{ // Non-empty square
			if(empty > 0)
			{ // Add the number of consecutive empty squares to the output string
				FEN += empty + "";
				empty = 0;
				idx++;
			}
			FEN += c + "";
			idx++;
		}
	}
	if(empty > 0)
	{
		FEN += empty + "";
	}
	return FEN + ' ' + activeColor + ' ' + castlingAvail + ' ' + epSquare + ' ' + halfmoveClock + ' ' + fullmoveNumber;
}


// this function returns an object with the following values
// --- ALWAYS ---
//  this.fromSq  = the sq the piece moved from (board square)
//  this.fromRow = the row the piece moved from
//  this.fromCol = the col the piece moved from
//  this.toSq    = the sq the piece moved to
//  this.toRow   = the row the piece moved to
//  this.toCol   = the col the piece moved to
// --- SOMETIMES ---
//  this.captPiece = the code of the piece that was captured
//  this.captSq    = the sq of the captured piece (different from toSq if en passant)
//  this.captRow   = the row of the captured piece (different from toSq if en passant)
//  this.captCol   = the col of the captured piece (different from toSq if en passant)
//
// if the move was a castle move, the 'from' location is where the king ends up
// and the 'to' location is where the rook ends up, because the starting points will
// never be the same.
// NOTE: the ouput of the castle from and to squares
// is different from the php version of this function
function previousMove(idx)
{
	var i;
	var fromIdx = false;
	var toIdx = false;
	var captIdx = false;

	if (undefined != idx)
		var thisMove = idx;
	else
		var thisMove = numMoves;

	var thatMove = thisMove - 1;

	var ep = FEN[thatMove].split(' ')[3];
	// this will return 71 when ep = '-'
	var epIdx = idx2sq(files.indexOf(ep.charAt(0)) + ( (Number(ep.charAt(1)) - 1) * 8));
	var thisFEN = expandFEN(FEN[thisMove].split(' ')[0]);
	var thatFEN = expandFEN(FEN[thatMove].split(' ')[0]);

	// start by checking for a castle move
	// this may not be the best way to go about it, but it's all i've got right now
	if (('w' == FEN[thatMove].split(' ')[1]) // it was white's move
		 && ( (-1 != FEN[thatMove].split(' ')[2].indexOf('K')) || (-1 != FEN[thatMove].split(' ')[2].indexOf('Q')) ) // and they could have castled
		 && ( (-1 == FEN[thisMove].split(' ')[2].indexOf('K')) && (-1 == FEN[thisMove].split(' ')[2].indexOf('Q')) ) ) // and now they can't
	{
		var backRank = thisFEN.substr(56,8);

		// check for proper piece position
		if (('K' == backRank.charAt(2)) && ('R' == backRank.charAt(3)) && (-1 != FEN[thatMove].split(' ')[2].indexOf('Q')))
		{
			fromIdx = 58; // the king's final position
			toIdx = 59;   // the rook's final position
		}
		else if (('K' == backRank.charAt(6)) && ('R' == backRank.charAt(5)) && (-1 != FEN[thatMove].split(' ')[2].indexOf('K')))
		{
			fromIdx = 62; // the king's final position
			toIdx = 61;   // the rook's final position
		}
	}
	else if (('b' == FEN[thatMove].split(' ')[1]) // it was black's move
		 && ( (-1 != FEN[thatMove].split(' ')[2].indexOf('k')) || (-1 != FEN[thatMove].split(' ')[2].indexOf('q')) ) // and they could have castled
		 && ( (-1 == FEN[thisMove].split(' ')[2].indexOf('k')) && (-1 == FEN[thisMove].split(' ')[2].indexOf('q')) ) ) // and now they can't
	{
		var backRank = thisFEN.substr(0,8);

		// check for proper piece position
		if (('k' == backRank.charAt(2)) && ('r' == backRank.charAt(3)) && (-1 != FEN[thatMove].split(' ')[2].indexOf('q')))
		{
			fromIdx = 2; // the king's final position
			toIdx = 3;   // the rook's final position
		}
		else if (('k' == backRank.charAt(6)) && ('r' == backRank.charAt(5)) && (-1 != FEN[thatMove].split(' ')[2].indexOf('k')))
		{
			fromIdx = 6; // the king's final position
			toIdx = 5;   // the rook's final position
		}
	}

	if ( ! fromIdx &&  ! toIdx) // other non-castling moves
	{
		// check for en passant captures first
		if ('w' == FEN[thatMove].split(' ')[1])
		{
			if (71 != epIdx && 'P' == thisFEN.charAt(epIdx)) // white capture black en passant
			{
				captIdx = epIdx + 8;
				this.captPiece = 'p';
			}
		}
		else // black's turn
		{
			if (71 != epIdx && 'p' == thisFEN.charAt(epIdx)) // black capture white en passant
			{
				captIdx = epIdx - 8;
				this.captPiece = 'P';
			}
		}

		// get the FROM square and TO square
		for (i = 0; i < 64; i++)
		{
			if (( ! captIdx || i != captIdx) && thisFEN.charAt(i) != thatFEN.charAt(i))
			{
				if ('1' == thisFEN.charAt(i))
					fromIdx = i;
				else
					toIdx = i;
			}
		}

		// check for all other captures (skip if we already have an en passant capture)
		if ( ! captIdx && '1' != thatFEN.charAt(toIdx))
		{
//      alert("Capture found @ square "+toIdx+"="+captIdx+" : "+thatFEN.charAt(toIdx));
			captIdx = toIdx;
			this.captPiece = thatFEN.charAt(toIdx);
		}
	}

	this.fromSq = idx2sq(fromIdx);
	this.fromRow = Math.floor(this.fromSq / 8);
	this.fromCol = this.fromSq % 8;
	this.toSq = idx2sq(toIdx);
	this.toRow = Math.floor(this.toSq / 8);
	this.toCol = this.toSq % 8;

	if (captIdx)
	{
		this.captSq = idx2sq(captIdx);
		this.captRow = Math.floor(this.captSq / 8);
		this.captCol = this.captSq % 8;
	}
}


// this function takes an index value from an expanded
// FEN and returns the board square number
// NOTE: function works both ways, will also do sq2idx
function idx2sq(idx)
{
	return (8 * (7 - Math.floor(idx / 8))) + (idx % 8);
}