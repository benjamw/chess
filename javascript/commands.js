// these functions interact with the server

function disableButtons( )
{
	if ( ! watchgame)
	{
		getObject("btnUndo").disabled = true;
		getObject("btnDraw").disabled = true;
		getObject("btnResign").disabled = true;
	}
}

function undo( )
{
	disableButtons( );
	document.gamedata.requestUndo.value = "yes";

	my_alert("gamedata.requestUndo = " + document.gamedata.requestUndo.value);

	document.gamedata.submit( );
}

function draw( )
{
	disableButtons( );
	document.gamedata.requestDraw.value = "yes";

	my_alert("gamedata.requestDraw = " + document.gamedata.requestDraw.value);

	document.gamedata.submit( );
}

function resigngame( )
{
	disableButtons( );
	document.gamedata.resign.value = "yes";

	my_alert("gamedata.resign = " + document.gamedata.resign.value);

	document.gamedata.submit( );
}

function displayMainmenu( )
{
	this.disabled = true;
	disableButtons( );
	window.open('index.php', '_self');
}

function reloadPage(btnReload)
{
	btnReload.disabled = true;
	disableButtons( );
	window.open('chess.php', '_self');
}

function downloadPGN( )
{
	window.open('./includes/openpgn.inc.php', '_self')
}

function replay( )
{
	if (document.gamemenu.btnReplay.value == "Replay")
	{
		document.gamemenu.btnReplay.value = "Continue";

		// pause the refresh timer
		clearTimeout(intervalId);

		// disable the board
		isBoardDisabled = true;

		// run the replay scripts
		var replayBoard = htmlBoard( );
		getObject('chessboard').innerHTML = replayBoard;

		// reset the moves with movable ones
		displayMoves(true);

		// get the FEN array
		currMoveIdx = FEN.length - 1;

		// display the captured pieces
		FENToCapt(numMoves);
		displayCaptPieces( );

		// make the replay buttons and hide game buttons
		getObject('gamebuttons').style.display = 'none';
		var navButtons = '<form id="navigation" action="">';
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

		// set the replay button actions
		getObject("start").onclick = function( ){moveJmp(-10000);};
		getObject("jmpback").onclick = function( ){moveJmp(-5);};
		getObject("prev").onclick = function( ){moveJmp(-1);};
		getObject("next").onclick = function( ){moveJmp(1);};
		getObject("jmpfwd").onclick = function( ){moveJmp(5);};
		getObject("end").onclick = function( ){moveJmp(10000);};
		getObject("invert").onclick = function( ){toggleInvert( );};
	}
	else if (document.gamemenu.btnReplay.value == "Continue")
	{
		// just refresh the page, everything resets itself
		window.location.replace('chess.php');
	}
}

function wakeUp( )
{
	if (confirm('You wish to send your opponent a wake up e-mail ?'))
	{
		document.wakeup.submit( );
	}
}

function promotepawn( )
{
	var blackPawnFound = false;
	var whitePawnFound = false;
	var i = -1;

	// search for the promoting pawn
	while ( ! blackPawnFound &&  ! whitePawnFound && i < 8)
	{
		i++;

		/* check for black pawn being promoted */
		if (board[0][i] == (BLACK | PAWN))
			blackPawnFound = true;

		/* check for white pawn being promoted */
		if (board[7][i] == (WHITE | PAWN))
			whitePawnFound = true;
	}

	/* to which piece is the pawn being promoted to? */
	var promotedTo = 0;
	for (var j = 0; j <= 3; j++)
	{
		if (document.gamedata.promotion[j].checked)
			promotedTo = parseInt(document.gamedata.promotion[j].value);
	}

	/* change pawn to promoted piece */
	var enemyColor = "black";
	if (blackPawnFound)
	{
		enemyColor = "white";
		board[0][i] = (BLACK | promotedTo);

		my_alert("Promoting to: (black) " + board[0][i]);

	}
	else if (whitePawnFound)
	{
		board[7][i] = (WHITE | promotedTo);

		my_alert("Promoting to: (white) " + board[7][i]);
	}
	else
	{
		alert("WARNING!: cannot find pawn being promoted!");
	}

	/* update board and database */
	document.gamedata.submit( );
}