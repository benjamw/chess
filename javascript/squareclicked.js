// this is the main function that interacts with the user everytime they click on a square

// called whenever a square is clicked on
var is1stClick = true;

function squareClickedFirst(row, col, isEmpty, curColor)
{
	if (getPieceColor(board[row][col]) == curColor)
	{
		document.gamedata.fromRow.value = row;
		document.gamedata.fromCol.value = col;

		var square = parseInt(row) * 8 + parseInt(col);
		highlight(getObject('tsq' + square), 'highlighted');

		is1stClick = false;
	}
	else
	{
		alert("I'm sorry, but you play the " + curColor +" pieces.");
	}

}

function squareClickedSecond(row,col,isEmpty,curColor)
{
//   var rookCol;
//   var rookToCol;
//   var epCol;
	var castle = 0;

	var square = parseInt(document.gamedata.fromRow.value) * 8 + parseInt(document.gamedata.fromCol.value);
	unhighlight(getObject('tsq' + square));
	is1stClick = true;

	// if we clicked the same piece, just deselect it and wait for first click again
	if ( (document.gamedata.fromRow.value == row)
		&& (document.gamedata.fromCol.value == col) )
	{
		document.gamedata.fromRow.value = "";
		document.gamedata.fromCol.value = "";
		return null;
	}
	else
	{
		/* if, on a player's second click, they click on one of their own pieces */
		/* act as if he was clicking for the first time (ie: select it) */
		/* unless the first click was a king and the second was a rook, */
		/* then check for a castle move */
		if (board[row][col] != 0)// if the square clicked is not empty...
		{
			if (getPieceColor(board[row][col]) == curColor) // and is our own piece...
			{
				if ('rook' == getPieceName(board[row][col])
					&& 'king' == getPieceName(board[document.gamedata.fromRow.value][document.gamedata.fromCol.value])
					&& confirm("Do you wish to castle?"))
				{
					if (0 < document.gamedata.fromCol.value - col) // if it's to a-side
						castle = 'a';
					else
						castle = 'h';
				}
				else // we want to change the selected piece
				{
					squareClickedFirst(row,col,isEmpty,curColor);
					return null;
				}
			}
		}

		var fromRow = parseInt(document.gamedata.fromRow.value);
		var fromCol = parseInt(document.gamedata.fromCol.value);
		document.gamedata.toRow.value = row;
		document.gamedata.toCol.value = col;

		if (isValidMove(fromRow,fromCol,row,col,-1,castle)) // (validation.js)
		{
			my_alert("Move is valid, updating game...");

			var thePiece = getPieceName(board[fromRow][fromCol]);

			/* if this is a castling move the rook must also be moved */
			if (thePiece == 'king' && (0 != castle || Math.abs(col - fromCol) == 2))
			{
				document.gamedata.castleMove.value = 'a';

				if ('h' == castle || 2 == (col - fromCol))
				{ // Kingside castling (would be == -2 if queenside)
					document.gamedata.castleMove.value = 'h';
				}
			}

			document.gamedata.submit( );
			return true;
		}
		else
		{
			document.gamedata.toRow.value = "";
			document.gamedata.toCol.value = "";

			alert("Invalid move:\n" + errMsg);
			return false;
		}
	}
}

function squareClicked(squareObj)
{
	var square = parseInt( squareObj.id.slice(3) );
	var col = square % 8;
	var row = (square-col) / 8;
	var isEmpty = (board[row][col] == 0);

	my_alert('squareClicked -> row = ' + row + ', col = ' + col + ', isEmpty = ' + isEmpty);

	var curTurn = FEN[numMoves].split(' ')[1];
	var curColor = "black";

	if ('w' == curTurn)
		curColor = "white";

	if (is1stClick)
	{ // No piece has been clicked yet
		if ( ! isEmpty)  // Not an empty square
			squareClickedFirst(row,col,isEmpty,curColor);
		else  // Clicked on an empty square
			return;
	}
	else  // The second click. A piece has already been marked with the first click
		squareClickedSecond(row,col,isEmpty,curColor);
}