// these functions are used to test the validity of moves
// DEBUG = false;

// global vars
var knightMove = [[-1, -2], [+1, -2], [-2, -1], [-2, +1], [-1, +2], [+1, +2], [+2, -1], [+2, +1]];
var diagonalMove = [[-1, -1], [-1, +1], [+1, +1], [+1, -1]];
var horzVertMove = [[-1, 0], [0, +1], [+1, 0], [0, -1]];
// The array 'direction' is a combination of diagonalMove and horzVertMove
// It could also be created using 'var direction = horzVertMove.concat(diagonalMove)'
// although the order of the elements would be different
var direction = [[-1, -1], [-1, 0], [-1, +1], [0, +1], [+1, +1], [+1, 0], [+1, -1], [0, -1]];
var pawnMove = [[+1, -1], [+1, 0], [+2, 0], [+1, +1]];


// object definition (used by isSafe)
function GamePiece( )
{
	this.piece = 0;
	this.dist = 0;
}


/* isSafe tests whether the square at testRow, testCol is safe */
/* for a piece of color testColor to travel to */
function isSafe(testRow, testCol, testColor)
{
	var i;
	var fromRow;
	var fromCol;
	var pieceFound = new Array( );
	var tmpPiece;
	var kingRow;
	var kingCol;
	var tmpIsSafe;
	/* NOTE: if a piece occupies the square itself,
		that piece does not participate in determining the safety of the square */

	/* IMPORTANT: note that if we're checking to see if the square is safe for a pawn
		we're moving, we need to verify the safety for En-passant */

	/* OPTIMIZE: cache results (if client-side game only, invalidate cache after each move) */

	/* AI NOTE: just because a square isn't entirely safe doesn't mean we don't want to
		move there; for instance, we may be protected by another piece */

	/* DESIGN NOTE: this function is mostly designed with CHECK checking in mind and
		may not be suitable for other purposes */

	my_alert("in isSafe(" + testRow + ", " + testCol + ", " + testColor + ")");

	var enemyColor = 0;
	if ('white' == testColor)
	{
		enemyColor = 128; /* 1000 0000 */
	}

	/* check for knights first */
	for (i = 0; i < 8; i++) // Check all eight possible knight moves
	{
		fromRow = testRow + knightMove[i][0];
		fromCol = testCol + knightMove[i][1];

		if (isInBoard(fromRow,fromCol))
		{
			if (board[fromRow][fromCol] == (KNIGHT | enemyColor))  // Enemy knight found
			{
				my_alert("isSafe -> knight found @ " + fromRow + "," + fromCol)
				return false;
			}
		}
	}

	/* tactic: start at test pos and check all 8 directions for an attacking piece */
	/* directions:
		black
		-----
		0 1 2
		7 * 3
		6 5 4
		-----
		white
	*/
	for (i = 0; i < 8; i++)
		pieceFound[i] = new GamePiece( );

	for (i = 1; i < 8; i++)
	{
		if ((testRow - i) >= 0 && (testCol - i) >= 0)
			if (0 == pieceFound[0].piece && board[testRow - i][testCol - i] != 0)
			{
				pieceFound[0].piece = board[testRow - i][testCol - i];
				pieceFound[0].dist = i;

				my_alert("isSafe -> pieceFound[0] = " + board[testRow - i][testCol - i] + "\ndist = " + i);
			}

		if ((testRow - i) >= 0)
			if (0 == pieceFound[1].piece && board[testRow - i][testCol] != 0)
			{
				pieceFound[1].piece = board[testRow - i][testCol];
				pieceFound[1].dist = i;

				my_alert("isSafe -> pieceFound[1] = " + board[testRow - i][testCol] + "\ndist = " + i);
			}

		if ((testRow - i) >= 0 && (testCol + i) < 8)
			if (0 == pieceFound[2].piece && board[testRow - i][testCol + i] != 0)
			{
				pieceFound[2].piece = board[testRow - i][testCol + i];
				pieceFound[2].dist = i;

				my_alert("isSafe -> pieceFound[2] = " + board[testRow - i][testCol + i] + "\ndist = " + i);
			}

		if ((testCol + i) < 8)
			if (0 == pieceFound[3].piece && board[testRow][testCol + i] != 0)
			{
				pieceFound[3].piece = board[testRow][testCol + i];
				pieceFound[3].dist = i;

				my_alert("isSafe -> pieceFound[3] = " + board[testRow][testCol + i] + "\ndist = " + i);
			}

		if (((testRow + i) < 8) && ((testCol + i) < 8))
			if ((pieceFound[4].piece == 0) && (board[testRow + i][testCol + i] != 0))
			{
				pieceFound[4].piece = board[testRow + i][testCol + i];
				pieceFound[4].dist = i;

				my_alert("isSafe -> pieceFound[4] = " + board[testRow + i][testCol + i] + "\ndist = " + i);
			}

		if ((testRow + i) < 8)
			if ((pieceFound[5].piece == 0) && (board[testRow + i][testCol] != 0))
			{
				pieceFound[5].piece = board[testRow + i][testCol];
				pieceFound[5].dist = i;

				my_alert("isSafe -> pieceFound[5] = " + board[testRow + i][testCol] + "\ndist = " + i);
			}

		if (((testRow + i) < 8) && ((testCol - i) >= 0))
			if ((pieceFound[6].piece == 0) && (board[testRow + i][testCol - i] != 0))
			{
				pieceFound[6].piece = board[testRow + i][testCol - i];
				pieceFound[6].dist = i;

				my_alert("isSafe -> pieceFound[6] = " + board[testRow + i][testCol - i] + "\ndist = " + i);
			}

		if (testCol - i >= 0)
			if (0 == pieceFound[7].piece && board[testRow][testCol - i] != 0)
			{
				pieceFound[7].piece = board[testRow][testCol - i];
				pieceFound[7].dist = i;

				my_alert("isSafe -> pieceFound[7] = " + board[testRow][testCol - i] + "\ndist = " + i);
			}
	}

	/* check pieces found for possible threats */
	for (i = 0; i < 8; i++)
	{
		if (pieceFound[i].piece != 0 && (pieceFound[i].piece & BLACK) == enemyColor)
		{
			switch (i)
			{
				/* diagonally: queen, bishop, pawn, king */
				case 0:
				case 2:
				case 4:
				case 6:
					if ((pieceFound[i].piece & COLOR_MASK) == QUEEN
					 || (pieceFound[i].piece & COLOR_MASK) == BISHOP)
					{
						my_alert("isSafe -> notKnight -> diagonal -> Q or B -> " + getPieceColor(pieceFound[i].piece) + " " + getPieceName(pieceFound[i].piece) + "\ndist = " + pieceFound[i].dist + "\ndir = " + i);
						return false;
					}

					if (1 == pieceFound[i].dist
					 && (pieceFound[i].piece & COLOR_MASK) == PAWN)
					{
						my_alert("isSafe -> notKnight -> diagonal -> Pawn -> " + getPieceColor(pieceFound[i].piece) + " " + getPieceName(pieceFound[i].piece) + "\ndist = " + pieceFound[i].dist + "\ndir = " + i);

						if (WHITE == enemyColor && (0 == i || 2 == i) )
						{
							return false;
						}
						else if (BLACK == enemyColor && (4 == i || 6 == i) )
						{
							return false;
						}
					}

					if (1 == pieceFound[i].dist
						&& (pieceFound[i].piece & COLOR_MASK) == KING)
					{
						my_alert("isSafe -> notKnight -> diagonal -> King -> " + getPieceColor(pieceFound[i].piece) + " " + getPieceName(pieceFound[i].piece) + "\ndist = " + pieceFound[i].dist + "\ndir = " + i);

						/* Are the kings next to each other? */
						if ((board[testRow][testCol] & COLOR_MASK) == KING)
						{
							return false;
						}

						/* save current board destination */
						tmpPiece = board[testRow][testCol];

						/* update board with move (client-side) */
						board[testRow][testCol] = pieceFound[i].piece;

						kingRow = 0;
						kingCol = 0;
						switch (i)
						{
							case 0:
								kingRow = testRow - 1;
								kingCol = testCol - 1;
								break;

							case 1:
								kingRow = testRow - 1;
								kingCol = testCol;
								break;

							case 2:
								kingRow = testRow - 1;
								kingCol = testCol + 1;
								break;

							case 3:
								kingRow = testRow;
								kingCol = testCol + 1;
								break;

							case 4:
								kingRow = testRow + 1;
								kingCol = testCol + 1;
								break;

							case 5:
								kingRow = testRow + 1;
								kingCol = testCol;
								break;

							case 6:
								kingRow = testRow + 1;
								kingCol = testCol - 1;
								break;

							case 7:
								kingRow = testRow;
								kingCol = testCol - 1;
								break;
						}

						board[kingRow][kingCol] = 0;

						/* if king needs to move into check to capture piece, isSafe( ) is true */
						tmpIsSafe = isInCheck(getOtherColor(testColor));

						/* restore board to previous state */
						board[kingRow][kingCol] = pieceFound[i].piece;
						board[testRow][testCol] = tmpPiece;

						/* if king CAN eat target without moving into check, return false */
						/* otherwise, continue checking other piecesFound */
						if ( ! tmpIsSafe)
						{
							return false;
						}
					}
					break;

				/* horizontally/vertically: queen, rook, king */
				case 1:
				case 3:
				case 5:
				case 7:
					if ((pieceFound[i].piece & COLOR_MASK) == QUEEN
						|| (pieceFound[i].piece & COLOR_MASK) == ROOK)
					{
						my_alert("isSafe -> notKnight -> horiz/vert -> Q or R -> " + getPieceColor(pieceFound[i].piece) + " " + getPieceName(pieceFound[i].piece) + "\ndist = " + pieceFound[i].dist + "\ndir = " + i);

						return false;
					}

					if (1 == pieceFound[i].dist
						&& (pieceFound[i].piece & COLOR_MASK) == KING)
					{
						my_alert("isSafe -> notKnight -> horiz/vert -> King -> " + getPieceColor(pieceFound[i].piece) + " " + getPieceName(pieceFound[i].piece) + "\ndist = " + pieceFound[i].dist + "\ndir = " + i);

						/* Are the kings next to each other? */
						if ((board[testRow][testCol] & COLOR_MASK) == KING)
						{
							return false;
						}

						/* save current board destination */
						tmpPiece = board[testRow][testCol];

						/* update board with move (client-side) */
						board[testRow][testCol] = pieceFound[i].piece;

						kingRow = 0;
						KingCol = 0;
						switch (i)
						{
							case 0:
								kingRow = testRow - 1; kingCol = testCol - 1;
								break;

							case 1:
								kingRow = testRow - 1; kingCol = testCol;
								break;

							case 2:
								kingRow = testRow - 1; kingCol = testCol + 1;
								break;

							case 3:
								kingRow = testRow;     kingCol = testCol + 1;
								break;

							case 4:
								kingRow = testRow + 1; kingCol = testCol + 1;
								break;

							case 5:
								kingRow = testRow + 1; kingCol = testCol;
								break;

							case 6:
								kingRow = testRow + 1; kingCol = testCol - 1;
								break;

							case 7:
								kingRow = testRow;     kingCol = testCol - 1;
								break;
						}

						board[kingRow][kingCol] = 0;

						/* if king needs to move into check to capture piece, isSafe( ) is true */
						tmpIsSafe = isInCheck(getOtherColor(testColor));

						/* restore board to previous state */
						board[kingRow][kingCol] = pieceFound[i].piece;
						board[testRow][testCol] = tmpPiece;

						/* if king CAN eat target without moving into check, return false */
						/* otherwise, continue checking other piecesFound */
						if (!tmpIsSafe)
						{
							return false;
						}
					}
					break;
			}
		}
	}

	my_alert("isSafe is true");

	return true;
}

function isValidMoveKing(fromRow, fromCol, toRow, toCol, tmpColor, castleSide)
{
	var i;
	var atkColor;

	// the king cannot move to a square occupied by a friendly piece
	// although the space may be occupied by the rook if it's a castle move
	if ( ! castleSide && 0 != board[toRow][toCol] && getPieceColor(board[toRow][toCol]) == tmpColor)
	{
		return false;
	}

	// if it's a castle move, every square between
	// the king and the final king location must
	// be empty and checked for attacking pieces
	if ('a' == castleSide || 'h' == castleSide)
	{
		// check to see if castling is valid before doing anything
		var CM = FEN[numMoves].split(' ')[2];
		my_alert(CM);
		my_alert(castleSide);

		// set the final locations of the castling pieces
		// and the original rook position
		var origRookPos;
		var finlRookPos;
		var finlKingPos;
		var dirK; var dirR;
		if ('a' == castleSide)
		{
			origRookPos = initpos.indexOf('R');
			finlRookPos = 3;
			finlKingPos = 2;

			if (2 != origKingPos)
			{
				dirK = (finlKingPos - origKingPos) / Math.abs(finlKingPos - origKingPos);
			}
			else
			{
				dirK = 0;
			}

			if (3 != origRookPos)
			{
				dirR = (finlRookPos - origRookPos) / Math.abs(finlRookPos - origRookPos);
			}
			else
			{
				dirR = 0;
			}
		}
		else if ('h' == castleSide)
		{
			origRookPos = initpos.lastIndexOf('R');
			finlRookPos = 5;
			finlKingPos = 6;

			if (6 != origKingPos)
			{
				dirK = (finlKingPos - origKingPos) / Math.abs(finlKingPos - origKingPos);
			}
			else
			{
				dirK = 0;
			}

			if (5 != origRookPos)
			{
				dirR = (finlRookPos - origRookPos) / Math.abs(finlRookPos - origRookPos);
			}
			else
			{
				dirR = 0;
			}
		}
		my_alert('origRookPos = '+origRookPos+'\nfinlRookPos = '+finlRookPos+'\nfinlKingPos = '+finlKingPos+'\ndirK = '+dirK+'\ndirR = '+dirR);

		var cSide;
		var cValid = true;
		if ('white' == tmpColor)
		{
			// if it's a-side, check to make sure that the Q is present in the castle indicator
			// and that the toCol is the same location as the original rook.  this removes errors
			// from having both rooks on the same side as well
			if ('a' == castleSide)
			{
				my_alert('white a = '+castleSide);
				cSide = CM.indexOf('Q');
				if ((-1 == cSide) || (toCol != origRookPos))
				{
					cValid = false;
				}
			}
			// for h-side look for the K and the rook location, same as above
			else if ('h' == castleSide)
			{
				my_alert('white h = '+castleSide);
				cSide = CM.indexOf('K');
				if ((-1 == cSide) || (toCol != origRookPos))
				{
					cValid = false;
				}
			}
		}
		else // black
		{
			if ('a' == castleSide)
			{
				my_alert('black a = '+castleSide);
				cSide = CM.indexOf('q');
				if ((-1 == cSide) || (toCol != origRookPos))
				{
					cValid = false;
				}
			}
			else if ('h' == castleSide)
			{
				my_alert('black h = '+castleSide);
				cSide = CM.indexOf('k');
				if ((-1 == cSide) || (toCol != origRookPos))
				{
					cValid = false;
				}
			}
		}

		if ( ! cValid)
		{
			errMsg = "You can only castle if the king or the castling rook has not moved yet";
			return false;
		}

		my_alert('cSide = ' + cSide + '\ncharAt = ' + CM.charAt(cSide + 1) +
			'\nindexOf = ' + files.indexOf(CM.charAt(cSide + 1)))

		if (-1 != files.indexOf(CM.charAt(cSide + 1)) && '' != CM.charAt(cSide + 1))
		{
			my_alert('Rooks are NOT fine as they are\nThey must be checked');
			origRookPos = files.indexOf(CM.charAt(cSide + 1));
		}
		else
		{
			my_alert('Rooks are fine as they are');
		}

		// check every square between the king and the final king location
		for (var i = origKingPos; i != (finlKingPos + dirK); i += dirK)
		{
			if (i < 0 || i > 7) // just a simple test in case things do get out of hand
				break;

			my_alert('i = ' + i + '\ndirK = ' + dirK + '\noK+d = ' + parseInt(origKingPos + dirK) + '\nfK+d = ' + parseInt(finlKingPos + dirK) +
				'\nboard = ' + board[fromRow][i] + '\norigRookPos = ' + origRookPos);

			if (board[fromRow][i] && (i != origRookPos) && (i != origKingPos))
			{
				errMsg = "Cannot jump over other pieces while castling";
				return false;
			}

			if ( ! isSafe(fromRow,i,tmpColor))
			{
				errMsg = "When castling, the king cannot start in check, move through a threatened square, or end in check";
				return false;
			}
		} // end of king for loop

		// check every square between the rook and the final rook location
		for (i = (origRookPos + dirR); i != (finlRookPos + dirR); i += dirR)
		{
			my_alert('i = ' + i + '\ndirR = ' + dirR + '\noR+d = ' + parseInt(origRookPos + dirR) + '\nfR+d = ' + parseInt(finlRookPos + dirR) +
				'\nboard = ' + board[fromRow][i] + '\norigRookPos = ' + origRookPos);

			if (board[fromRow][i] && i != origKingPos)
			{
				errMsg = "Cannot jump over other pieces while castling";
				return false;
			}
		} // end of rook for loop
		my_alert('All castle tests PASSED\n\nso far...');
		return true;
	} // end of castle tests

	/* temporarily move king to destination to see if in check */
	var tmpPiece = board[toRow][toCol];
	board[toRow][toCol] = board[fromRow][fromCol];
	board[fromRow][fromCol] = 0;

	/* The king does not move to a square that is attacked by an enemy piece */
	if ('white' == tmpColor)
		atkColor = BLACK;
	else
		atkColor = WHITE;

	if (isInCheck(tmpColor))
	{
		/* return king to original position */
		board[fromRow][fromCol] = board[toRow][toCol];
		board[toRow][toCol] = tmpPiece;

		my_alert("king -> destination not safe!");

		errMsg = "Cannot move into check.";
		return false;
	}

	/* return king to original position */
	board[fromRow][fromCol] = board[toRow][toCol];
	board[toRow][toCol] = tmpPiece;

	/* NORMAL MOVE: */
	if (1 >= Math.abs(toRow - fromRow) && 1 >= Math.abs(toCol - fromCol))
	{
		my_alert("king -> normal move");
		return true;
	}
	/* CASTLING: leave this here for orthodox castling moves in normal games */
	else if ( (518 != id960 && (-1 == fromCol || -2 == fromCol)) || (518 == id960 && fromRow == toRow && 4 == fromCol && Math.abs(toCol - fromCol) == 2) )
	{
		/*
		The following conditions must be met:
				* The King and rook must occupy the same rank (or row).
				* The rook that makes the castling move has not yet moved in the game.
				* The king has not yet moved in the game

				* all these conditions are checked for in the creation of the FEN, so just use the data there.
		*/
		my_alert("isValidMoveKing -> Castling");

		var rookCol = 0;
		if (toCol - fromCol == 2)
			rookCol = 7;

		// check to see if castling is valid before doing anything
		var CM = FEN[numMoves].split(' ')[2];

		var cSide;
		var cValid = true;
		if ('white' == tmpColor)
		{
			// see above castling for comments
			if (2 == toCol && -1 == (cSide = CM.indexOf('Q')))
				cValid = false;
			else if (6 == toCol && -1 == (cSide = CM.indexOf('K')))
				cValid = false;
		}
		else // black
		{
			if (2 == toCol && -1 == (cSide = CM.indexOf('q')))
				cValid = false;
			else if (6 == toCol && -1 == (cSide = CM.indexOf('k')))
				cValid = false;
		}

		if ( ! cValid)
		{
			errMsg = "You can only castle if the king or the castling rook has not moved yet";
			return false;
		}


		/*
				* All squares between the rook and king before the castling move are empty.
		*/
		var tmpStep = (toCol - fromCol) / 2;
		for (i = 4 + tmpStep; i != rookCol; i += tmpStep)
			if (board[fromRow][i] != 0)
			{
				my_alert("king -> castling -> square not empty");

				errMsg = "You can only castle if there are no pieces between the rook and the king";
				return false;
			}

		/*
				* The king is not in check.
				* The king does not move over a square that is attacked by an enemy piece during the castling move
		*/

		/* NOTE: the king's destination has already been checked, so */
		/* all that's left is it's initial position and it's final one */
		if (isSafe(fromRow, fromCol, tmpColor)
		 && isSafe(fromRow, fromCol + tmpStep, tmpColor))
		{
			my_alert("king -> castling -> VALID!");

			return true;
		}
		else
		{
			my_alert("king -> castling -> moving over attacked square");

			errMsg = "When castling, the king cannot move over a square that is attacked by an enemy piece";
			return false;
		}
	}
	/* INVALID MOVE */
	else
	{
		my_alert("king -> completely invalid move\nfrom " + fromRow + ", " + fromCol + "\nto " + toRow + ", " + toCol);
		errMsg = "Kings cannot move like that.";
		return false;
	}

	if (DEBUG)
	{
		alert("king -> unknown error");
		return false;
	}
}

/* checks whether a pawn is making a valid move */
function isValidMovePawn(fromRow,fromCol,toRow,toCol,tmpDir,epCol)
{
	if (arguments.length < 6) // Was epCol not passed as a parameter to this function?
		epCol = -1; // Make sure that epCol is defined
	if ( ( (toRow - fromRow) / Math.abs(toRow - fromRow) ) != tmpDir)
	{
		errMsg = "Pawns cannot move backwards, only forward.";
		return false;
	}

	/* standard move */
	if ((tmpDir * (toRow - fromRow) == 1) && (toCol == fromCol) && (board[toRow][toCol] == 0))
		return true;
	/* first move double jump - white */
	if ((tmpDir == 1) && (fromRow == 1) && (toRow == 3) && (toCol == fromCol) && (board[2][toCol] == 0) && (board[3][toCol] == 0))
		return true;
	/* first move double jump - black */
	if ((tmpDir == -1) && (fromRow == 6) && (toRow == 4) && (toCol == fromCol) && (board[5][toCol] == 0) && (board[4][toCol] == 0))
		return true;
	/* standard eating DJ-NOTE: Shouldn't we check that the pawn being eaten is of the correct color? */
	else if ((tmpDir * (toRow - fromRow) == 1) && (Math.abs(toCol - fromCol) == 1) && (board[toRow][toCol] != 0))
		return true;
	/* en passant - white */
	else if ((tmpDir == 1) && (fromRow == 4) && (toRow == 5) && (board[4][toCol] == (PAWN | BLACK)))
	{
		/* can only move en passant if last move is the one where the black pawn moved up two */
		if (epCol == toCol ||
			(numMoves >= 0 && prevMove.fromRow == 6 && prevMove.toRow == 4
							&& prevMove.toCol == toCol))
			return true;
		else
		{
			errMsg = "Pawns can only capture en passant immediately after an opponent advanced his pawn two squares.";
			return false;
		}
	}
	/* en passant - black */
	else if ((tmpDir == -1) && (fromRow == 3) && (toRow == 2) && (board[3][toCol] == PAWN))
	{
		/* can only move en passant if last move is the one where the white pawn moved up two */
		if ( 0 <= numMoves && files.charAt(toCol) == FEN[numMoves].split(' ')[3].charAt(0))
			return true;
		else
		{
			errMsg = "Pawns can only capture en passant immediately after an opponent advanced his pawn two squares.";
			return false;
		}
	}
	else
	{
		errMsg = "Pawns cannot move like that.";
		return false;
	}
}

/* checks whether a knight is making a valid move */
function isValidMoveKnight(fromRow, fromCol, toRow, toCol)
{
	errMsg = "Knights cannot move like that.";
	if (Math.abs(toRow - fromRow) == 2)
	{
		if (Math.abs(toCol - fromCol) == 1)
			return true;
		else
			return false;
	}
	else if (Math.abs(toRow - fromRow) == 1)
	{
		if (Math.abs(toCol - fromCol) == 2)
			return true;
		else
			return false;
	}
	else
	{
		return false;
	}
}

/* checks whether a bishop is making a valid move */
function isValidMoveBishop(fromRow, fromCol, toRow, toCol)
{
	var i;
	if (Math.abs(toRow - fromRow) == Math.abs(toCol - fromCol))
	{
		if (toRow > fromRow)
		{
			if (toCol > fromCol)
			{
				for (i = 1; i < (toRow - fromRow); i++)
					if (board[fromRow + i][fromCol + i] != 0)
					{
						errMsg = "Bishops cannot jump over other pieces.";
						return false;
					}
			}
			else
			{
				for (i = 1; i < (toRow - fromRow); i++)
					if (board[fromRow + i][fromCol - i] != 0)
					{
						errMsg = "Bishops cannot jump over other pieces.";
						return false;
					}
			}

			return true;
		}
		else
		{
			if (toCol > fromCol)
			{
				for (i = 1; i < (fromRow - toRow); i++)
					if (board[fromRow - i][fromCol + i] != 0)
					{
						errMsg = "Bishops cannot jump over other pieces.";
						return false;
					}
			}
			else
			{
				for (i = 1; i < (fromRow - toRow); i++)
					if (board[fromRow - i][fromCol - i] != 0)
					{
						errMsg = "Bishops cannot jump over other pieces.";
						return false;
					}
			}

			return true;
		}
	}
	else
	{
		errMsg = "Bishops cannot move like that.";
		return false;
	}
}

/* checks wether a rook is making a valid move */
function isValidMoveRook(fromRow, fromCol, toRow, toCol)
{
	var i;

	if (toRow == fromRow)
	{
		if (toCol > fromCol)
		{
			for (i = (fromCol + 1); i < toCol; i++)
			{
				if (board[fromRow][i] != 0)
				{
					errMsg = "Rooks cannot jump over other pieces.";
					return false;
				}
			}
		}
		else
		{
			for (i = (toCol + 1); i < fromCol; i++)
			{
				if (board[fromRow][i] != 0)
				{
					errMsg = "Rooks cannot jump over other pieces.";
					return false;
				}
			}
		}

		return true;
	}
	else if (toCol == fromCol)
	{
		if (toRow > fromRow)
		{
			for (i = (fromRow + 1); i < toRow; i++)
			{
				if (board[i][fromCol] != 0)
				{
					errMsg = "Rooks cannot jump over other pieces.";
					return false;
				}
			}
		}
		else
		{
			for (i = (toRow + 1); i < fromRow; i++)
			{
				if (board[i][fromCol] != 0)
				{
					errMsg = "Rooks cannot jump over other pieces.";
					return false;
				}
			}
		}

		return true;
	}
	else
	{
		errMsg = "Rooks cannot move like that.";
		return false;
	}
}

/* this function checks whether a queen is making a valid move */
function isValidMoveQueen(fromRow, fromCol, toRow, toCol)
{
	if (isValidMoveRook(fromRow, fromCol, toRow, toCol) || isValidMoveBishop(fromRow, fromCol, toRow, toCol))
		return true;

	if (errMsg.search("jump") == -1)
		errMsg = "Queens cannot move like that.";
	else
		errMsg = "Queens cannot jump over other pieces.";

	return false;
}

/* this functions checks to see if curColor is in check */
function isInCheck(curColor)
{
	var i,j;
	var targetKing = getPieceCode(curColor, "king");

	/* find king */
	for (i = 0; i < 8; i++)
	{
		for (j = 0; j < 8; j++)
		{
			if (board[i][j] == targetKing)
			{
				/* verify it's location is safe */
				return ! isSafe(i, j, curColor);
			}
		}
	}

	/* the next lines will hopefully NEVER be reached */
	errMsg = "CRITICAL ERROR: KING MISSING!"
	return false;
}

/* Ignoring pins, could the piece on the from-square move to the to-square? */
function isValidNoPinMove(fromRow, fromCol, toRow, toCol, epCol, castleSide)
{
	var isValid;
	var tmpDir = 1;
	var curColor = "white";

	if (board[fromRow][fromCol] & BLACK)
	{
		tmpDir = -1;
		curColor = "black";
	}

	isValid = false;
	my_alert("isValidNoPinMove -> " + (board[fromRow][fromCol] & COLOR_MASK));
	switch(board[fromRow][fromCol] & COLOR_MASK)
	{
		case PAWN:
			isValid = isValidMovePawn(fromRow, fromCol, toRow, toCol, tmpDir, epCol);
			break;

		case KNIGHT:
			isValid = isValidMoveKnight(fromRow, fromCol, toRow, toCol);
			break;

		case BISHOP:
			isValid = isValidMoveBishop(fromRow, fromCol, toRow, toCol);
			break;

		case ROOK:
			isValid = isValidMoveRook(fromRow, fromCol, toRow, toCol);
			break;

		case QUEEN:
			isValid = isValidMoveQueen(fromRow, fromCol, toRow, toCol);
			break;

		case KING:
			isValid = isValidMoveKing(fromRow, fromCol, toRow, toCol, curColor, castleSide);
			break;

		default:  /* ie: not implemented yet */
			my_alert("unknown game piece");
	}
	return isValid;
}

function isValidMove(fromRow, fromCol, toRow, toCol, epCol, castleSide)
{
	var curColor;
	var isValid;
	var tmpPiece;
	var tmpEnPassant;

	if ( ! isValidNoPinMove(fromRow, fromCol, toRow, toCol, epCol, castleSide) )
		return false; // The piece on the from-square doesn't even move in this way

	/* now that we know the move itself is valid, let's make sure we're not moving into check */
	/* NOTE: we don't need to check for the king since it's covered by isValidMoveKing( ) */

	curColor = "white";
	if (board[fromRow][fromCol] & BLACK)
		curColor = "black";

	isValid = true;

	if ((board[fromRow][fromCol] & COLOR_MASK) != KING)
	{
		my_alert("isValidMove -> are we moving into check?");

		/* save current board destination */
		tmpPiece = board[toRow][toCol];

		/* is it an en passant capture? Then remove the captured pawn */
		tmpEnPassant = 0;
		if (((board[fromRow][fromCol] & COLOR_MASK) == PAWN) && (Math.abs(toCol - fromCol) == 1) && (tmpPiece == 0))
		{
			tmpEnPassant = board[fromRow][toCol];
			board[fromRow][toCol] = 0;
		}

		/* update board with move (client-side) */
		board[toRow][toCol] = board[fromRow][fromCol];
		board[fromRow][fromCol] = 0;

		/* are we in check now? */
		if (isInCheck(curColor))
		{
			my_alert("isValidMove -> moving into check -> CHECK!");

			/* if so, invalid move */
			errMsg = "Cannot move into check.";
			isValid = false;
		}

		/* restore board to previous state */
		board[fromRow][fromCol] = board[toRow][toCol];
		board[toRow][toCol] = tmpPiece;
		if (tmpEnPassant != 0)
		{
			board[fromRow][toCol] = tmpEnPassant;
		}
	}

	my_alert("isValidMove returns " + isValid);

	return isValid;
}

function canSquareBeBlocked(testRow, testCol, testColor)
{
	var i,j;
	var fromRow;
	var fromCol;
	/*
	NOTE: This function is similar to isSafe( ); however, the pawn detection
	is different. While the original function checks pawns moving diagonally
	or en-passant, this function doesn't.
	Since this function is intended for checkmate detection, specifically the
	canBlockAttacker( ) function, it must validate pawns moving forward.
	Also, king is not /allowed/ to block a square.
	NOTE: testColor is the attacker color!
	*/

	//var DEBUG=true;

	my_alert("in canSquareBeBlocked(" + testRow + ", " + testCol + ", " + testColor + ")");

	var enemyColor = WHITE;  // Attacking
	var myColor = BLACK;    // Blocking

	if (testColor == 'black')
	{
		enemyColor = BLACK; /* 1000 0000 */
		myColor = WHITE;
	}

	/* check for knights first */
	for (i = 0; i < 8; i++) // Check all eight possible knight moves
	{
		fromRow = testRow + knightMove[i][0];
		fromCol = testCol + knightMove[i][1];
		if (isInBoard(fromRow, fromCol))
			if (board[fromRow][fromCol] == (KNIGHT | myColor))  // Knight found
				if (isValidMove(fromRow, fromCol, testRow, testCol))
					return true;  // It can move and block the attack
	}

	/* tactic: start at test pos and check all 8 directions for an attacking piece */
	/* directions:    BLACK:    WHITE:
			0 1 2         2 1 0     6 5 4
			7 * 3         3 * 7     7 * 3
			6 5 4         4 5 6     0 1 2
	*/
	for (j = 0; j < 8; j++)   // Look for pieces in all directions
	{
		fromRow = testRow;
		fromCol = testCol;
		for (i = 1; i < 8; i++) // Distance from the test square
		{
			fromRow += direction[j][0];
			fromCol += direction[j][1];
			if (isInBoard(fromRow, fromCol))
			{ // if square is in board..
				if (board[fromRow][fromCol] != 0)
				{ // We found the first piece in this direction
					if ((board[fromRow][fromCol] & BLACK) == myColor)
					{ // It is my piece
						if (isValidMove(fromRow, fromCol, testRow, testCol))
							return true;  // It can move and block the attack
					}
					break;    // No need to look further in this direction
				}
			}
			else
				break;  // We fell off the edge of the board
		}
	}
	return false; // The attack cannot be blocked
}

/* canBeCaptured returns true if the piece at testRow, testCol can be captured */
function canBeCaptured(testRow, testCol, epCol)
{
	var i;
	var tmpDir = -1;
	var enemyColor = BLACK;
	/* DESIGN NOTE: this function is designed only with CAPTURE checking in mind and should
		not be used for other purposes, e.g. if there is no piece (or a king) on the given square */
	/* Both normal captures and en passant captures are checked. The epCol parameter
		 should contain the column number of the en passant square or -1 if there is none.
		 If epCol >= 0 it indicates that we are replying to a pawn double advance move */

	if (board[testRow][testCol] & BLACK)
	{
		tmpDir = 1;
		enemyColor = WHITE;
	}

	var thePiece = getPieceName(board[testRow][testCol]);
	var atkSquare = getAttackers(testRow, testCol, enemyColor);  // Find all attackers

	for (i = 0; i < atkSquare.length; i++)  // Are the attackers pinned or can they capture?
		if(isValidMove(atkSquare[i][0], atkSquare[i][1], testRow, testCol))
			return true;  // The piece can be captured

	// If thePiece is a pawn can it by captured en passant?
	if (thePiece == 'pawn' && ((testRow == 3 && enemyColor == BLACK) || (testRow == 4 && enemyColor == WHITE)))
	{ // The pawn is on the correct row for a possible e.p. capture
		if (testCol > 0 && board[testRow][testCol-1] == (PAWN | enemyColor))
			if (board[testRow + tmpDir][testCol] == 0) // It's not a regular capture
					if (isValidMove(testRow, testCol-1, testRow + tmpDir, testCol, epCol))
						return true;  // En passant capture

		if (testCol < 7 && board[testRow][testCol+1] == (PAWN | enemyColor))
			if (board[testRow + tmpDir][testCol] == 0) // It's not a regular capture
					if (isValidMove(testRow, testCol+1, testRow + tmpDir, testCol. epCol))
						return true;  // En passant capture
	}
	return false; // The piece cannot be captured
}

/* Find all pieces of color atkColor that attack the given square */
/* Note: Even if a piece attacks a square it may not be able to move there */
/* Note: En passant captures are not considered by this function */
function getAttackers(toRow, toCol, atkColor)
{
	var i,j;
	var fromRow;
	var fromCol;
	var atkSquare = new Array( );

	/* check for knights first */
	for (i = 0; i < 8; i++) { // Check all eight possible knight moves
		fromRow = toRow + knightMove[i][0];
		fromCol = toCol + knightMove[i][1];
		if (isInBoard(fromRow, fromCol))
			if (board[fromRow][fromCol] == (KNIGHT | atkColor)) // Enemy knight found
					atkSquare[atkSquare.length] = [fromRow, fromCol];
	}
	/* tactic: start at test square and check all 8 directions for an attacking piece */
	/* directions:
		0 1 2
		7 * 3
		6 5 4
	*/

	for (j = 0; j < 8; j++)   // Look in all directions
	{
		fromRow = toRow;
		fromCol = toCol;
		for (i = 1; i < 8; i++) // Distance from thePiece
		{
			fromRow += direction[j][0];
			fromCol += direction[j][1];
			if (isInBoard(fromRow, fromCol))
			{
				if (board[fromRow][fromCol] != 0)
				{ // We found the first piece in this direction
					if((board[fromRow][fromCol] & BLACK) == atkColor) // It is an enemy piece
					{
						if(isAttacking(board[fromRow][fromCol], fromRow, fromCol, getPieceColor(board[fromRow][fromCol]), toRow, toCol))
							atkSquare[atkSquare.length] = [fromRow, fromCol]; // An attacker found
					}
					break;    // No need to look further in this direction
				}
			}
			else
				break;
		}
	}
	return atkSquare;
}

/* Is the given square attacked by a piece of color atkColor? */
function isAttacked(toRow, toCol, atkColor)
{
	return getAttackers(toRow, toCol, atkColor).length > 0;
}

/* Generate moves for a rook, bishop or queen placed at the from-square */
function genSlideMoves(fromRow, fromCol, moveDir)
{
	var i,j;
	var toRow;
	var toCol;
	var toSquare = new Array( ); // Store the generated moves
	var enemyColor = BLACK;
	if (board[fromRow][fromCol] & BLACK)
	{
		enemyColor = WHITE;
	}
	for (j = 0; j < moveDir.length; j++)  // Check all (valid) directions
	{
		toRow = fromRow;
		toCol = fromCol;
		for (i = 1; i < 8; i++) // Distance from the piece
		{
			toRow += moveDir[j][0];
			toCol += moveDir[j][1];
			if (isInBoard(toRow, toCol))
			{
				if (board[toRow][toCol] != 0)
				{ // We found the first piece in this direction
					if((board[toRow][toCol] & BLACK) == enemyColor)  // It's an enemy piece
					{
						if(isValidMove(fromRow, fromCol, toRow, toCol))
							toSquare[toSquare.length] = [toRow, toCol]; // A capture
					}
					break;    // No need to look further in this direction
				}
				else  // an empty square
				{
					if(isValidMove(fromRow, fromCol, toRow, toCol))
						toSquare[toSquare.length] = [toRow, toCol]; // Move to an empty square
				}
			}
			else
				break;
		}
	}
	return toSquare;
}

/* Generate all moves for the piece at the given square */
/* Currently this function is only used to test for stalemate.
	 Therefore castling moves are not checked as they are not relevant
	 for that purpose */
function genPieceMoves(fromRow, fromCol)
{
	var i;
	var thePiece;
	var toSquare;
	var forwardDir;
	var toRow;
	var toCol;
	var enemyColor = BLACK;
	if (board[fromRow][fromCol] & BLACK)
	{
		enemyColor = WHITE;
	}
	thePiece = board[fromRow][fromCol];

	toSquare = new Array( );

	switch(thePiece & COLOR_MASK)
	{
		case PAWN:
			forwardDir = 1;
			if (enemyColor == WHITE)
				forwardDir = -1;

			for (i = 0; i < 4; i++) {
				toRow = fromRow + pawnMove[i][0] * forwardDir;
				toCol = fromCol + pawnMove[i][1];
				if (isInBoard(toRow, toCol))
					if (board[toRow][toCol] == 0 || (board[toRow][toCol] & BLACK) == enemyColor)
						if(isValidMove(fromRow, fromCol, toRow, toCol))
							toSquare[toSquare.length] = [toRow, toCol];
			}
			break;

		case ROOK:
			toSquare = genSlideMoves(fromRow, fromCol, horzVertMove);
			break;

		case KNIGHT:
			for (i = 0; i < 8; i++) { // Check all eight possible knight moves
				toRow = fromRow + knightMove[i][0];
				toCol = fromCol + knightMove[i][1];
				if (isInBoard(toRow, toCol))
					if (board[toRow][toCol] == 0 || (board[toRow][toCol] & BLACK) == enemyColor)
						if(isValidMove(fromRow, fromCol, toRow, toCol))
							toSquare[toSquare.length] = [toRow, toCol];
			}
			break;

		case BISHOP:
			toSquare = genSlideMoves(fromRow, fromCol, diagonalMove);
			break;

		case QUEEN:
			toSquare = genSlideMoves(fromRow, fromCol, direction);
			break;

		case KING:
			for (i = 0; i < 8; i++) { // Check all eight possible king moves
				toRow = fromRow + direction[i][0];
				toCol = fromCol + direction[i][1];
				if (isInBoard(toRow, toCol))
					if (board[toRow][toCol] == 0 || (board[toRow][toCol] & BLACK) == enemyColor)
						if(isValidMove(fromRow, fromCol, toRow, toCol))
							toSquare[toSquare.length] = [toRow, toCol];
			}
			break;
	}

	return toSquare;
}

/* Generate all possible moves for the side indicated by the myColor parameter */
function genAllMoves(myColor)
{
	var i,j;
	var moves = new Array( );
	for (i = 0; i < 8; i++)     // For all board rows
	{
		for (j = 0; j < 8; j++)   // Check all columns
		{
			if(board[i][j] != 0 && ((board[i][j] & BLACK) == myColor))
			{
				if(typeof moves[i] == 'undefined') {
					moves[i] = new Array( );
				}

				moves[i][j] = genPieceMoves(i, j);
			}
		}
	}
	return moves;
}

/* Count how many different moves are possible in the current position for myColor */
function countMoves(myColor)
{
	var i,j;
	var moves = genAllMoves(myColor);
	var count = 0;
	for (i in moves)      // For all board rows
	{
		for (j in moves[i])   // Check all columns
		{
			count += moves[i][j].length;
		}
	}
	return count;
}

function isFiftyMoveDraw(FEN)
{ // Returns true if the game is drawn due to the fifty move draw rule (no captures or pawn moves)
	return FEN.split(' ')[4] >= 100;
}

function isThirdTimePosDraw(FEN)
{ // Returns true if this is the third time that the exact same position arises with the same side to move
	var i;
	var currentPos = FEN[FEN.length - 1].split(' ')[0];
	var count = 0;
	for (i = 0; i < FEN.length - 1; i++)
	{
		if(currentPos == FEN[i].split(' ')[0])
		{
			count++;
		}
	}
	return count >= 2;
}



function my_alert(thing)
{
	if (DEBUG)
	{
		alert(thing);
	}
}
