// these are variables that are always needed (global)
// but shouldn't be cluttering up the javascript output

// piece code constants
var PAWN   =   1;
var KNIGHT =   2;
var BISHOP =   4;
var ROOK   =   8;
var QUEEN  =  16;
var KING   =  32;
var WHITE  =   0;
var BLACK  = 128;
var COLOR_MASK = 127;

// the empty board
var board = [
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0],
	[0,0,0,0,0,0,0,0]
];

// the initial position of the pieces
// var initpos = FEN[0].split(' ')[0].split('/')[7]; // it's generated in php to avoid possible conflicting data

// the initial positions of the rooks and king
var origARookPos = initpos.indexOf('R');
var origKingPos  = initpos.indexOf('K');
var origHRookPos = initpos.lastIndexOf('R');

// the number of moves
var numMoves = FEN.length - 1;

// the captured pieces
var captPieces = [[],[]];

// global var used for error messages
var errMsg = '';

// files string for conversion
var files = 'abcdefgh';