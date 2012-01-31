<?php /* these functions are used to start a new game */

require_once 'chessutils.inc.php';

function initBoard($pos = 'RNBQKBNR')
{
	global $board;

	/* clear board */
	for ($i = 0; $i < 8; $i++)
	{
		for ($j = 0; $j < 8; $j++)
		{
			$board[$i][$j] = 0;
		}
	}

	/* setup main pieces */
	for ($i = 0; $i < 8; $i++)
	{
		switch ( substr($pos,$i,1) )
		{
			case "K" :
				$board[0][$i] = WHITE | KING;
				$board[7][$i] = BLACK | KING;
				break;

			case "Q" :
				$board[0][$i] = WHITE | QUEEN;
				$board[7][$i] = BLACK | QUEEN;
				break;

			case "R" :
				$board[0][$i] = WHITE | ROOK;
				$board[7][$i] = BLACK | ROOK;
				break;

			case "B" :
				$board[0][$i] = WHITE | BISHOP;
				$board[7][$i] = BLACK | BISHOP;
				break;

			case "N" :
				$board[0][$i] = WHITE | KNIGHT;
				$board[7][$i] = BLACK | KNIGHT;
				break;
		}
	}

	/* setup pawns */
	for ($i = 0; $i < 8; $i++)
	{
		$board[1][$i] = WHITE | PAWN;
		$board[6][$i] = BLACK | PAWN;
	}
	call($board);
}

function createNewGame($game_id,$id960 = "")
{
	global $mysql;

	$num_moves = -1;

	$query = "
		DELETE FROM ".T_HISTORY."
		WHERE h_game_id = '{$_SESSION['game_id']}'
	";
	$mysql->query($query, __LINE__, __FILE__);

	$pos = id960_to_pos($id960); // (chessutils.inc.php)

	$initFEN = strtolower($pos)."/pppppppp/8/8/8/8/PPPPPPPP/{$pos} w KQkq - 0 1";

	$query = "
		INSERT INTO ".T_HISTORY."
			(h_time, h_game_id, h_fen)
		VALUES
			(NOW( ), '{$_SESSION['game_id']}', '{$initFEN}')
	";
	$mysql->query($query, __LINE__, __FILE__);

	initBoard($pos);
}

?>