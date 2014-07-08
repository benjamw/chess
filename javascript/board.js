
function getObject(obj) {
	if (document.getElementById) {  // Mozilla, FireFox, Explorer 5+, Opera 5+, Konqueror, Safari, iCab, Ice, OmniWeb 4.5
		if (typeof obj == "string") {
			if (document.getElementById(obj)) {
				return document.getElementById(obj);
			} else {
				return document.getElementsByName(obj)[0];
			}
		} else {
			return obj.style;
		}
	}
	if (document.all) {       // Explorer 4+, Opera 6+, iCab, Ice, Omniweb 4.2-
		if (typeof obj == "string") {
			return document.all(obj);
		} else {
			return obj.style;
		}
	}
	if (document.layers) {      // Netscape 4, Ice, Escape, Omniweb 4.2-
		if (typeof obj == "string") {
			return document.layers(obj);
		} else {
			return obj.style;
		}
	}
	alert('Object not found: ' + obj);
	return false;
}


function isGameDrawn( )
{
	var i,j;

	// Stalemate?  // is all this needed, it is generated in php, so...
	if (gameState == 'stalemate')
	{
		var myColor = WHITE;

		if (0 <= numMoves && 'b' == FEN[FEN.length - 1].split(' ')[1])
		{
			myColor = BLACK;
		}

		if (0 == countMoves(myColor))
		{
			alert('Draw (Stalemate)\nYou should offer your opponent a draw');
		}

		return "stalemate";
	}

	// Is the game drawn due to insufficient material to checkmate?
	var count = 0;
	var canCheckmate = false;

	for (i = 0; i < 8; i++)
	{
		for (j = 0; j < 8; j++)
		{
			if (board[i][j] != 0 && (board[i][j] & COLOR_MASK) != KING)
			{
				if ((board[i][j] & COLOR_MASK) != KNIGHT && (board[i][j] & COLOR_MASK) != BISHOP)
					canCheckmate = true;
				else
					count++;
			}
		}
	}
	if (count < 2 && ! canCheckmate)
	{
		alert('Draw (Insufficient material to checkmate)\nYou should offer your opponent a draw');
		return "material";
	}

	// Is the game drawn because this is the third time that the exact same position arises?
	if (numMoves >= 0 && isThirdTimePosDraw(FEN))
	{
		alert('Draw (This position has occurred three times)\nYou should offer your opponent a draw');
		return "3";
	}

	// Draw because of no capture or pawn move for the last 50 moves?
	if (numMoves >= 0 && isFiftyMoveDraw(FEN[FEN.length-1]))
	{
		alert('Draw (50 move rule)\nYou should offer your opponent a draw');
		return "50";
	}

	return false;
}


function displayCaptPieces( )
{
	var i,j;
	var color = 'white';
	var html = '<div>';
	var piece = '';
	var item;

	for(i = 0; i < captPieces.length; i++)
	{
		for(j = 0; j < captPieces[i].length; j++)
		{
			piece = color + '_' + captPieces[i][j];
			html += '<img src="images/' + currentTheme + '/' + piece + '.' + ((-1 !== currentTheme.indexOf('gnuchess')) ? 'png' : 'gif') + '" width="';
			html += parseInt(50 * 3 / 5) + '" height="' + parseInt(50 * 3 / 5) + '" alt="' + piece + '" />';
		}

		html += "</div>\n<div>";
		color = 'black';
	}

	html += '</div>';
	getObject('captures').innerHTML = html;
}


if (0 < numMoves) { // if we have not made a move yet, don't get the previous move
	var prevMove = new previousMove( ); // the previous move info as object
}

var takenPiece = 0; // the captured piece img in the captures section
var captEnPass = 0; // the square the en passant captured pawn was on
function unhighlightCurMove( )
{
	unhighlight(getObject('tsq' + prevMove.fromSq));
	unhighlight(getObject('tsq' + prevMove.toSq));

	if (takenPiece) // if we have a captured piece highlighted
	{
		unhighlight(takenPiece); // unhighlight it
		takenPiece = 0; // and erase the var so we don't keep highlighting it
	}

	if (captEnPass) // if we have an en passant capture
	{
		unhighlight(captEnPass); // unhighlight it
		captEnPass = 0; // and erase the var so we don't keep highlighting it
	}
}


function highlightCurMove( )
{
	var item;

	// check for en passant move
	if (undefined != prevMove.captSq && prevMove.captSq != prevMove.toSq)
	{
		captEnPass = getObject('tsq' + prevMove.captSq);
	}

	if (prevMove.captPiece)
	{
		if ('w' == pieceColor[prevMove.captPiece])
		{
			item = 'white_' + pieceLtrToName[prevMove.captPiece.toLowerCase( )];
		}
		else
		{
			item = 'black_' + pieceLtrToName[prevMove.captPiece.toLowerCase( )];
		}

		var capt = getObject('captures').getElementsByTagName('img');

		var i;
		for (i = 0; i < capt.length; i++)
		{
			if (capt[i].alt == item)
			{
				takenPiece = capt[i];
				break;
			}
		}
	}

	highlight(getObject('tsq' + prevMove.fromSq), 'highlighted');
	setTimeout('highlightCurMoveTo( )', 300);
	setTimeout('unhighlightCurMove( )', 900);
}


function highlightCurMoveTo( )
{
	if (takenPiece)
	{
		highlight(takenPiece, 'taken_highlighted');

		if (captEnPass)
		{
			highlight(captEnPass, 'taken_highlighted');
			highlight(getObject('tsq' + prevMove.toSq), 'highlighted');
		}
		else
		{
			highlight(getObject('tsq' + prevMove.toSq), 'taken_highlighted');
		}
	}
	else
	{
		highlight(getObject('tsq' + prevMove.toSq), 'highlighted');
	}
}


function displayCurFEN(moveIdx)
{
	if (undefined != moveIdx)
	{
		getObject('FENblock').innerHTML = FEN[moveIdx];
	}
	else
	{
		getObject('FENblock').innerHTML = FEN[FEN.length - 1];
	}
}


// these will throw errors, but initializing them as 'undefined' is the
// only way to ensure all-around compatibility no matter what the original colors are.
function moveTo(objMoveId)
{
	var theBoard;

	if (0 < currMoveIdx) // don't try to reset the empty span, it throws errors
	{
		unhighlight(getObject('m' + currMoveIdx)); // reset the previous move box background color
	}

	currMoveIdx = parseInt(objMoveId.id.slice(1)); // get the move number
	FENToBoard(FEN[currMoveIdx]); // convert that FEN to the board var
	displayCurFEN(currMoveIdx); // display that FEN
	FENToCapt(currMoveIdx); // get the captures up to that point
	displayCaptPieces( ); // display those captures
	theBoard = htmlBoard( ); // convert the board var to html code
	getObject('chessboard').innerHTML = theBoard; // display that board
	highlight(getObject('m' + currMoveIdx), 'curmove_highlighted'); // change the move box background color
}


function moveJmp(moveDelta)
{
	var moveIdx = currMoveIdx;

	if (moveIdx + moveDelta > FEN.length - 1)
	{
		moveIdx = FEN.length - 1;
	}
	else if (moveIdx + moveDelta < 0)
	{
		moveIdx = 0;
	}
	else
	{
		moveIdx += moveDelta;
	}

	moveTo(getObject('m' + moveIdx + ''));
}


function displayMoves(replay)
{
	var i;
	var alt = '';
	var objGamebody = getObject('gamebody');
	var theMoves = '\n<span id="m0"></span>';
	var moveId = 1;
	theMoves += '\n<table class="moveList">\n';

	for (i = 0; i < moves.length; i++)
	{
		if ( (i + 1) % 2 == 0)
		{
			alt = ' class="alt"';
		}
		else
		{
			alt = '';
		}

		if ('1' == isGameOver || replay || 'mate' == gameState)
		{
			theMoves += '<tr'+alt+'>\n<td class="mn">' + (i+1) + '.</td>\n';
			theMoves += '<td id="m' + (moveId) + '" class="wm" onclick="moveTo(this);">' + moves[i][0] + '</td>\n';
			theMoves += '<td id="m' + (moveId+1) + '" class="bm" onclick="moveTo(this);">' + moves[i][1] + '</td>\n</tr>';
			moveId = moveId + 2;
		}
		else
		{
			theMoves += '<tr'+alt+'>\n<td class="mn">' + (i+1) + '.</td>\n<td class="wm">';
			theMoves += moves[i][0] + '</td>\n<td class="bm">' + moves[i][1] + '</td>\n</tr>';
		}
	}

	theMoves += '\n</table>\n';

	if ('' != result)
	{
		theMoves += '<span class="ctr">Result: ' + result + '</span>\n';
	}

	objGamebody.innerHTML = theMoves;
}


function toggleInvert( )
{
	if ('black' == perspective)
		perspective = 'white';
	else
		perspective = 'black';

	theBoard = htmlBoard( );
	getObject('chessboard').innerHTML = theBoard;
}


function htmlBoard( )
{ // Returns the HTML-code for the current chessboard (Note: Fixed square size and theme)
	var i,j,k;
	var classWSquare;
	var classBSquare;
	var classHeader;
	var fileLabel;
	var xtra;
	var mtra;
	var colorside;
	var invertBoard = (perspective == 'black');
	var rank = 8;
	var rankLabel = rank;

	if ('' == isBoardDisabled && ! watchgame)
	{
		classWSquare = 'light_enabled';
		classBSquare = 'dark_enabled';
		classHeader = 'header_enabled';
	}
	else
	{
		classWSquare = 'light_disabled';
		classBSquare = 'dark_disabled';
		classHeader = 'header_disabled';
	}

	var sqBackground = [classBSquare, classWSquare];

	if (invertBoard)
	{
		rankLabel = 1;
		colorside = "white";
	}
	else
	{
		colorside = "black";
	}

	j = 1;

	theBoard = '\n<div id="theBoard">\n';
	theBoard += '<div class="' + classHeader + ' ' + colorside + 'corner">&nbsp;<\/div>\n';

	for(i = 0; i < 8; i++)
	{
		if(invertBoard)
			fileLabel = Files[7-i];
		else
			fileLabel = Files[i];

		theBoard += '<div id="file_t' + i + '" class="' + classHeader + ' horz">' + fileLabel + '<\/div>\n';
	}

	theBoard += '<div class="' + classHeader + ' ' + colorside + 'corner">&nbsp;<\/div>\n';
	theBoard += '<div id="rank_l' + rank-- + '" class="' + classHeader + ' vert">' + rankLabel + '</div>\n';

	for (k = 63; k >= 0; k--)
	{
		if ((k+1) % 8 == 0)
		{
			i = k - 7;

			if (invertBoard)
				i = 63 - i;
		}
		else
		{
			if (invertBoard)
				i--;
			else
				i++;
		}

		var row = parseInt(i / 8);
		var col = i % 8;

		if (prevMove && row == prevMove.fromRow && col == prevMove.fromCol && '' == isBoardDisabled && ! watchgame && lastMoveIndicator)
			xtra = " fromSquare";
		else if (prevMove && row == prevMove.toRow && col == prevMove.toCol && '' == isBoardDisabled && ! watchgame && lastMoveIndicator)
			xtra = " toSquare";
		else
			xtra = "";

		theBoard += '<div id="tsq' + i + '" class="' + sqBackground[j] + xtra + '">';
		var piece = '';
		var source = '';

		if(board[row][col] != 0)
		{
			piece = getPieceColor(board[row][col]) + '_' + getPieceName(board[row][col]);
			source = 'images/' + currentTheme + '/' + piece + '.' + ((-1 !== currentTheme.indexOf('gnuchess')) ? 'png' : 'gif'); // Update the square
			theBoard += '<img alt="' + piece + '" id="sq' + i + '" ';
			theBoard += 'src="' + source + '" width="50" height="50" />';
		}
		else
		{
			theBoard += '';
		}
		theBoard += '<\/div>\n';
		if ( (k % 8) === 0 )
		{
			theBoard += '<div id="rank_r' + (rank+1) + '" class="' + classHeader + ' vert">' + rankLabel + '<\/div>\n';
			if (k != 0)
			{
				if(invertBoard)
					rankLabel = 9 - rank;
				else
					rankLabel = rank;

				theBoard += '<div id="rank_l' + rank-- + '" class="' + classHeader + ' vert">' + rankLabel + '</div>\n';
			}
		}
		else
		{
			j = 1 - j;
		}
	}

	if ("white" == colorside)
		colorside = "black";
	else
		colorside = "white";

	theBoard += '<div class="' + classHeader + ' ' + colorside + 'corner">&nbsp;<\/div>\n';

	for (i = 0; i < 8; i++)
	{
		if (invertBoard)
			fileLabel = Files[7-i];
		else
			fileLabel = Files[i];

		xtra = "";mtra = ""; // erase any previous values
		if ("518" != id960 && "header_disabled" != classHeader) // if we are not in a normal game and not disabled
		{
			if ("c" == fileLabel || "g" == fileLabel)
			{
				xtra = "<span class=\"kingto\">K</span>";
				mtra = "<span class=\"spacer\">K</span>";
			}
			else if ("d" == fileLabel)
			{
				xtra = "<span class=\"rookato\">R</span>";
				mtra = "<span class=\"spacer\">R</span>";
			}
			else if ("f" == fileLabel)
			{
				xtra = "<span class=\"rookhto\">R</span>";
				mtra = "<span class=\"spacer\">R</span>";
			}

			var LorigARookPos = origARookPos;
			var LorigKingPos  = origKingPos;
			var LorigHRookPos = origHRookPos;

			if (invertBoard)
			{
				LorigARookPos = 7 - LorigARookPos;
				LorigKingPos  = 7 - LorigKingPos;
				LorigHRookPos = 7 - LorigHRookPos;
			}

			if (i == LorigARookPos)
				fileLabel = '<span class="origarook">' + fileLabel + '</span>';
			else if (i == LorigKingPos)
				fileLabel = '<span class="origking">' + fileLabel + '</span>';
			else if (i == LorigHRookPos)
				fileLabel = '<span class="orighrook">' + fileLabel + '</span>';
		}

		theBoard += '<div id="file_b' + i + '" class="' + classHeader + ' horz">' + mtra + fileLabel + xtra + '<\/div>\n';
	}

	theBoard += '<div class="' + classHeader + ' ' + colorside + 'corner">&nbsp;<\/div>\n<\/div>\n';
	return theBoard;
}


// only do disabled = true for btnWakeUp, it is set as disabled if no email is present.

FENToBoard(FEN[FEN.length - 1]); // save the last entry in the FEN array to the board
var theBoard = htmlBoard( ); // The HTML code for the board
var currMoveIdx = 0;
var intervalId = 0;
window.onload = function( )
{
	var i;
	var lastMove;
	var navButtons;
	var gameIdDisplay;
	var invertBoard = (perspective == 'black');

	getObject('chessboard').innerHTML = theBoard;
	displayCurFEN( );
	FENToCapt(numMoves);
	displayCaptPieces( );


	if (0 != gameId) // is it a database game ?
	{
		gameIdDisplay = 'Game #' + gameId;
		getObject('btnPGN').disabled = false;
	}
	else // or a PGN file game
	{
		gameIdDisplay = 'PGN Game';
	}

	if ( ! watchgame)
	{
		if ('518' != id960) // if it's a Chess960 game
		{
			gameIdDisplay += ' - ' + id960 // display the id
		}
		else // or a regular game
		{
			getObject("castle").style.display = 'none';
		}
	}

	if ('1' != isBoardDisabled && ! watchgame)
	{
		if (0 < numMoves)
		{
			getObject("btnUndo").disabled = false;
		}

		getObject("btnDraw").disabled = false;
		getObject("btnResign").disabled = false;
	}

	if ( ! watchgame) // are we playing the game
	{
		displayMoves( );

		getObject("btnReload").disabled = false;
		getObject("btnReplay").disabled = false;
		getObject("btnReload").onclick = function( ) { reloadPage(this); };
		getObject("btnReplay").onclick = function( ) { replay( ); };


		if (0 < moves.length) // if there are moves
		{
			lastMove = moves.length + '-'; // get the move number

			if ('' != moves[moves.length-1][1]) // if we are showing a black move
			{
				getObject('curmove').innerHTML = lastMove + ' ... ' + moves[moves.length-1][1];
			}
			else // we are showing a white move
			{
				getObject('curmove').innerHTML = lastMove + ' ' + moves[moves.length-1][0];
			}

			if ('1' != isGameOver)
			{
				getObject("curmove").onclick = function( ) { highlightCurMove( ); };
			}
		}

		if ('check' == gameState)
		{
			getObject('checkmsg').style.display = '';
			getObject('checkmsg').innerHTML = 'Check !';

			// convert the board border to red if in check
			var divs = document.all ? document.all : document.getElementById("theBoard").getElementsByTagName("div");

			for ( var i = 0; i < divs.length; i++)
			{
				if (divs[i].className.match(/.*?(horz|vert).*?/))
				{
					divs[i].style.backgroundColor = "#BF2F35"; // TODO : stylefix
				}
			}
		}

		if ('' != statusMessage)
		{
			getObject('statusmsg').style.display = '';
			getObject('statusmsg').innerHTML = statusMessage;

			// if the statusMessage says anything about undo's
			// prevent multiple undo's from being requested
			if (statusMessage.match(/ undo /i))
			{
				getObject('btnUndo').disabled = true;
			}
		}
	}
	else // or just watching the game
	{
		displayMoves(true);
	}

	getObject('gameid').innerHTML = gameIdDisplay;
	getObject('players').innerHTML = players;

	getObject('btnMainMenu').disabled = false;
	getObject('btnPGN').disabled = false;

	getObject('btnMainMenu').onclick = function( ) { displayMainmenu( ); };
	getObject('btnPGN').onclick = function( ) { downloadPGN( ); };

	if ( ! watchgame)
	{
		getObject("btnUndo").onclick = function( ) { undo( ); };
		getObject("btnDraw").onclick = function( ) { draw( ); };
		getObject("btnResign").onclick = function( ) { resigngame( ); };
	}

	if ('1' == isGameOver || watchgame || 'mate' == gameState) // Allow game replay
	{
		if ( ! watchgame)
		{
			getObject('gamebuttons').style.display = 'none';
			getObject('btnWakeUp').disabled = true;
			getObject('btnReload').disabled = true;
			getObject('btnReplay').disabled = true;
		}

		currMoveIdx = FEN.length - 1;
		navButtons = '<form id="navigation" action="">';
		navButtons += '<span id="navbuttons">';
		navButtons += '<input id="start" title="Start of game" type="button" value="Start" />';
		navButtons += '<input id="jmpback" title="Go back five halfmoves" type="button" value="&nbsp;&lt;&lt;&nbsp;" />';
		navButtons += '<input id="prev" title="Go back one halfmove" type="button" value="&nbsp;&lt;&nbsp;" />';
		navButtons += '<input id="next" title="Go forward one halfmove" type="button" value="&nbsp;&gt;&nbsp;" />';
		navButtons += '<input id="jmpfwd" title="Go forward five halfmoves" type="button" value="&nbsp;&gt;&gt;&nbsp;" />';
		navButtons += '<input id="end" title="End of game" type="button" value="End" /> &nbsp; ';
		navButtons += '<input id="invert" title="Invert Board" type="button" value="Invert" />';
		navButtons += '</span>';
		navButtons += '</form>';
		getObject('gamenav').innerHTML = navButtons;
		getObject('start').onclick = function( ) { moveJmp(-10000); };
		getObject('jmpback').onclick = function( ) { moveJmp(-5); };
		getObject('prev').onclick = function( ) { moveJmp(-1); };
		getObject('next').onclick = function( ) { moveJmp(1); };
		getObject('jmpfwd').onclick = function( ) { moveJmp(5); };
		getObject('end').onclick = function( ) { moveJmp(10000); };
		getObject('invert').onclick = function( ) { toggleInvert( ); };
	}
	else // game is not over and we are not replaying it
	{
		isGameDrawn( ); // Alert the players it's stalemate, 50 move draw or the same position has occurred three times

		if (true == isPlayersTurn)
		{ // No need to set event handlers unless it's the player's move
			for(i = 0; i < 64; i++)
			{
				getObject('tsq' + i).onclick = function( ) { squareClicked(this); };
			}
			getObject('btnWakeUp').disabled = true;
		}

		if (autoreload > 0 && ! DEBUG) // if we need the board refreshed
		{
			intervalId = setTimeout("window.location.replace('chess.php')", autoreload * 1000); // start the refresh loop
		}
	}
}