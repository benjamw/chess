<?php
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
//  Author: Dave Lerner <http://Dave-L.com>                                  //
//  ------------------------------------------------------------------------ //
//  Adapted from Online Chess Club (OCC) version 1.0.10, which was written   //
//  by Michael Speck <http://lgames.sf.net> and published under the GNU      //
//  General Public License.                                                  //
//  ------------------------------------------------------------------------ //

/**
 * class ChessGame
 *
 * @package chess
 * @subpackage game
*/
define("gsRunning"   , 0);
define("gsMate"      , 1);
define("gsStalemate" , 2);
define("gsCheck"     , 3);


define('_MD_CHESS_ERROR',             'Error');
define('_MD_CHESS_FENBAD_LENGTH',          'invalid length');
define('_MD_CHESS_FENBAD_FIELD_COUNT',     'wrong number of fields');
define('_MD_CHESS_FENBAD_PP_INVALID',      'piece placement invalid');
define('_MD_CHESS_FENBAD_AC_INVALID',      'active color invalid');
define('_MD_CHESS_FENBAD_CA_INVALID',      'castling availability invalid');
define('_MD_CHESS_FENBAD_EP_INVALID',      'en passant target_square invalid');
define('_MD_CHESS_FENBAD_HC_INVALID',      'halfmove clock invalid');
define('_MD_CHESS_FENBAD_FN_INVALID',      'fullmove number invalid');
define('_MD_CHESS_FENBAD_MATERIAL',        'insufficient mating material');
define('_MD_CHESS_FENBAD_IN_CHECK',        'player to move cannot have opponent in check');
define('_MD_CHESS_FENBAD_CA_INCONSISTENT', 'castling availability inconsistent with piece placement');
define('_MD_CHESS_FENBAD_EP_COLOR',        'en passant target square wrong color');
define('_MD_CHESS_FENBAD_EP_NO_PAWN',      'en passant target square for nonexistent pawn');

/**
 * The purpose of this class is to handle chess moves.
 *
 * An instantiation of this class comprises the data essential for handling chess
 * moves in a specific game, and provides the requisite methods.
 *
 * - Input:      Game state and proposed move.
 * - Processing: Check the legality of the move, and update the game state if the move is legal.
 * - Output:     Indication of the move's legality, and the (possibly) updated game state.
 *
 * In addition to the above, there are utility methods for converting between Standard Algebraic
 * Notation (SAN) and a notation similar to Long Algebraic Notation.
 *
 * @package chess
 * @subpackage game
 */
class ChessGame {

	/**
		* Indicates whether object is valid.
		*
		* If empty string (''), indicates this is a valid object; otherwise contains an error message.
	 * Should be checked after creating an instance of this class.
		*
		* @var string $error
		*/
	var $error;

	 /**
		* gamestate
		*
	 * The game state is represented as an array with the following elements:
	 *
	 *  - 'fen_piece_placement'
	 *  - 'fen_active_color'
	 *  - 'fen_castling_availability'
	 *  - 'fen_en_passant_target_square'
	 *  - 'fen_halfmove_clock'
	 *  - 'fen_fullmove_number'
	 *  - 'pgn_result'
	 *  - 'pgn_fen'
	 *  - 'pgn_movetext'
	 *
	 * The elements prefixed with 'fen_' are standard Forsyth-Edwards Notation (FEN) elements,
	 * and the elements prefixed with 'pgn_' are standard Portable Game Notation (PGN) elements.
	 *
	 * Each element is a string.
		*
		* @var array $gamestate
		*/
	var $gamestate;

	 /**
		* board
		*
	 * A 64-element array, constructed from fen_piece_placement, is used for handling moves.
	 * Its indices are related to the standard tile coordinates as follows:
	 *
		* <pre>
	 * 8 | 56 57 58 59 60 61 62 63
	 * 7 | 48 49 50 51 52 53 54 55
	 * 6 | 40 41 42 43 44 45 46 47
	 * 5 | 32 33 34 35 36 37 38 39
	 * 4 | 24 25 26 27 28 29 30 31
	 * 3 | 16 17 18 19 20 21 22 23
	 * 2 |  8  9 10 11 12 13 14 15
	 * 1 |  0  1  2  3  4  5  6  7
	 *    ------------------------
	 *      a  b  c  d  e  f  g  h
		* </pre>
	 *
	 * For example, $board[17] is tile b3 and $board[55] is tile h7.
		*
		* @var array $board
		*/
	var $board;

	 /**
		* for auto-completion of moves
		* @var string $ac_move
		*/
	var $ac_move;

	 /**
		* array of white's pieces
		* @var array $w_figures
		*/
	var $w_figures;

	 /**
		* array of black's pieces
		* @var array $b_figures
		*/
	var $b_figures;


	 /**
	 * updated by handleMove, not used now but might be used in future
		* @var string $last_move
		*/
	var $last_move;

	 /**
	 * updated by handleMove, not used now but might be used in future
		* @var string $captured_piece
		*/
	var $captured_piece;

// --------------
// PUBLIC METHODS
// --------------

/**
 * constructor
 *
 * If a failure occurs, $this->error is set to a string containing an error message;
 * otherwise $this->error is set to an empty string.
 *
 * Example:
 * <pre>
 *    $chessgame = new ChessGame($fen);
 *    if (!empty($chessgame->error)) {
 *      echo "'$fen' invalid: $chessgame->error\n";
 *    }
 * </pre>
 *
 * @param mixed $param  If $param is an array, an existing game is loaded using $param as the nine-element gamestate array described above.
 * If $param is a non-empty string, a new game is created using $param as a FEN setup position.
 * Otherwise, a new game is created using the standard starting position.
 */
function ChessGame($param = null)
{
	// for now
	$this->browsing_mode  = 0;

	if (is_array($param)) {

		$this->gamestate = $param;
		$this->error     = '';

	} elseif (is_string($param) and !empty($param)) {

		$this->error = $this->init_gamestate($param);

	} else {

		$this->init_gamestate();
		$this->error = '';
	}
}

/**
 * Handle a move.
 *
 * @param string $move
 * @return array A two-element array:
 *  - $move_performed: true if the move was performed and the game state has been updated, false otherwise
 *  - $move_result_text: text message
 */
function move($move)
{
	empty($this->error) or trigger_error(_MD_CHESS_ERROR, E_USER_ERROR);
	return $this->handleMove($move);
}

/**
 * get game state
 *
 * @return array
 */
function gamestate()
{
	empty($this->error) or trigger_error(_MD_CHESS_ERROR, E_USER_ERROR);
	return $this->gamestate;
}

// ----------------------------------------------------------------
// PRIVATE METHODS - intended for use only by methods of this class
// ----------------------------------------------------------------

/**
 * Initialize gamestate for a new game.
 *
 * If a non-empty string $fen is provided, the game is initialized using $fen as a FEN setup position.
 * Otherwise the game is initialized using the standard starting position.
 *
 * @param  string  $fen
 * @return string  empty string on success, or error message on failure
 *
 * @access private
 */
function init_gamestate($fen = null)
{
	$this->gamestate = array();

	if (!empty($fen)) {
		$setup = true;
	} else {
		$setup = false;
		$fen   = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
	}

	// check that data is not unreasonably short or long
	if (strlen($fen) < 23 or strlen($fen) > 100) {
		return _MD_CHESS_FENBAD_LENGTH; // invalid length
	}

	$fen_data = explode(' ', $fen);

	if (count($fen_data) != 6) {
		return _MD_CHESS_FENBAD_FIELD_COUNT; // wrong number of fields
	}

	$this->gamestate['fen_piece_placement']          = $fen_data[0];
	$this->gamestate['fen_active_color']             = $fen_data[1];
	$this->gamestate['fen_castling_availability']    = $fen_data[2];
	$this->gamestate['fen_en_passant_target_square'] = $fen_data[3];
	$this->gamestate['fen_halfmove_clock']           = $fen_data[4];
	$this->gamestate['fen_fullmove_number']          = $fen_data[5];
	$this->gamestate['pgn_fen']                      = $setup ? $fen : null;
	$this->gamestate['pgn_result']                   = '*';
	$this->gamestate['pgn_movetext']                 = '*';

	if (!$this->fen_piece_placement_to_board())
	{
		return _MD_CHESS_FENBAD_PP_INVALID; // piece_placement invalid
	}
	elseif ($this->gamestate['fen_active_color'] != 'w' and $this->gamestate['fen_active_color'] != 'b')
	{
		return _MD_CHESS_FENBAD_AC_INVALID; // active_color invalid
	}
	// Since fen_piece_placement_to_board() checked $fen for the correct number of fields above, $castling_availability is non-empty.
	elseif ($this->gamestate['fen_castling_availability'] != '-' and !preg_match('/^(?:K\w?)?(?:Q\w?)?(?:k\w?)?(?:q\w?)?$/', $this->gamestate['fen_castling_availability']))
	{
		return _MD_CHESS_FENBAD_CA_INVALID; // castling_availability invalid
	}
	elseif ($this->gamestate['fen_en_passant_target_square'] != '-' and !preg_match('/^[a-h][36]$/', $this->gamestate['fen_en_passant_target_square']))
	{
		return _MD_CHESS_FENBAD_EP_INVALID; // en_passant_target_square invalid
	}
	elseif (!preg_match('/^\d{0,4}$/', $this->gamestate['fen_halfmove_clock']))
	{
		return _MD_CHESS_FENBAD_HC_INVALID; // halfmove_clock invalid
	}
	elseif (!preg_match('/^\d{0,4}$/', $this->gamestate['fen_fullmove_number']) or $this->gamestate['fen_fullmove_number'] < 1)
	{
		return _MD_CHESS_FENBAD_FN_INVALID; // fullmove_number invalid
	}
	elseif ($this->insufficient_mating_material())
	{
		return _MD_CHESS_FENBAD_MATERIAL; // insufficient mating material
	}
	elseif (($this->gamestate['fen_active_color'] == 'w' and $this->kingIsUnderAttack('b', 'w'))
		or   ($this->gamestate['fen_active_color'] == 'b' and $this->kingIsUnderAttack('w', 'b')))
	{
		return _MD_CHESS_FENBAD_IN_CHECK; // player to move cannot have opponent in check
	}
	elseif ((strstr($this->gamestate['fen_castling_availability'], 'K') and ($this->board[ 4] != 'wK' or $this->board[ 7] != 'wR'))
		or   (strstr($this->gamestate['fen_castling_availability'], 'Q') and ($this->board[ 4] != 'wK' or $this->board[ 0] != 'wR'))
		or   (strstr($this->gamestate['fen_castling_availability'], 'k') and ($this->board[60] != 'bK' or $this->board[63] != 'bR'))
		or   (strstr($this->gamestate['fen_castling_availability'], 'q') and ($this->board[60] != 'bK' or $this->board[56] != 'bR')))
	{
		return _MD_CHESS_FENBAD_CA_INCONSISTENT; // castling availability inconsistent with piece placement
	}
	elseif (($this->gamestate['fen_en_passant_target_square'] != '-' and $this->gamestate['fen_en_passant_target_square']{1} == 3 and $this->gamestate['fen_active_color'] != 'b')
		or   ($this->gamestate['fen_en_passant_target_square'] != '-' and $this->gamestate['fen_en_passant_target_square']{1} == 6 and $this->gamestate['fen_active_color'] != 'w'))
	{
		return _MD_CHESS_FENBAD_EP_COLOR; // en passant target square wrong color
	}
	elseif ($this->gamestate['fen_en_passant_target_square'] != '-' and $this->gamestate['fen_en_passant_target_square']{1} == 3
		and $this->board[$this->boardCoordToIndex($this->gamestate['fen_en_passant_target_square']{0} . '4')] != 'wP')
	{
		return _MD_CHESS_FENBAD_EP_NO_PAWN; // en passant target square for nonexistent pawn
	}
	elseif ($this->gamestate['fen_en_passant_target_square'] != '-' and $this->gamestate['fen_en_passant_target_square']{1} == 6
		and $this->board[$this->boardCoordToIndex($this->gamestate['fen_en_passant_target_square']{0} . '5')] != 'bP')
	{
		return _MD_CHESS_FENBAD_EP_NO_PAWN; // en passant target square for nonexistent pawn
	}

	#echo "In " . __CLASS__ . '::' . __FUNCTION__ . "\n";#*#DEBUG#
	#var_dump('gamestate', $this->gamestate);#*#DEBUG#

	// save the current FEN to the board
	$this->fen_piece_placement_to_board( );
	return ''; // successful
}

/**
 * Check whether a path is blocked.
 *
 * check a series of tiles given a start, an end tile
 * which is not included to the check and a position
 * change for each iteration. return true if not blocked.
 * all values are given for 1dim board.
 *
 * @param int $start
 * @param int $end
 * @param int $change
 * @return bool
 *
 * @access private
 */
function pathIsNotBlocked($start, $end, $change)
{
	for ($pos = $start; $pos != $end; $pos += $change) {

		#echo "path: $pos: '$this->board[$pos]' "; #*#DEBUG#

		if (!$this->is_empty_tile($pos))
			return false;
	}

	return true;
}

/**
 * Get path.
 *
 * get the empty tiles between start and end as an 1dim array.
 * whether the path is clear is not checked.
 *
 * @param int $start
 * @param int $end
 * @param int $change
 * @return array
 *
 * @access private
 */
function getPath($start, $end, $change)
{
	$path = array();
	for ($pos = $start; $pos != $end; $pos += $change) {
		$path[] = $pos;
	}

	return $path;
}

/**
 * get path change
 *
 * get the change value that must be added to create
 * the 1dim path for figure moving from fig_pos to
 * dest_pos. it is assumed that the movement is valid!
 * no additional checks as in tileIsReachable are
 * performed. rook, queen and bishop are the only
 * units that can have empty tiles in between.
 *
 * @param string $fig
 * @param int $fig_pos
 * @param int $dest_pos
 * @return int
 *
 * @access private
 */
function getPathChange($fig, $fig_pos, $dest_pos)
{
	$change = 0;

	$fy = floor($fig_pos / 8);
	$fx = $fig_pos % 8;
	$dx = $dest_pos % 8;
	$dy = floor($dest_pos / 8);

	switch ( $fig ) {

		/* bishop */
		case 'B':
			$change =  ($dy < $fy) ? -8 : 8;
			$change += ($dx < $fx) ? -1 : 1;
			break;

		/* rook */
		case 'R':
			if ($fx == $dx) {
				$change = ($dy < $fy) ? -8 : 8;
			} else {
				$change = ($dx < $fx) ? -1 : 1;
			}
			break;

		/* queen */
		case 'Q':
			if (abs($fx - $dx) == abs($fy - $dy)) {
				$change =  ($dy < $fy) ? -8 : 8;
				$change += ($dx < $fx) ? -1 : 1;
			} elseif ($fx == $dx) {
				$change = ($dy < $fy) ? -8 : 8;
			}
			else {
				$change = ($dx < $fx) ? -1 : 1;
			}
			break;
	}

	return $change;
}

/**
 * Check whether a tile is reachable.
 *
 * check whether dest_pos is in reach for unit of fig_type
 * at tile fig_pos. it is not checked whether the tile
 * itself is occupied but only the tiles in between.
 * this function does not check pawns.
 *
 * @param string $fig
 * @param int $fig_pos
 * @param int $dest_pos
 * @return bool
 *
 * @access private
 */
function tileIsReachable($fig, $fig_pos, $dest_pos)
{
	if ( $fig_pos==$dest_pos) return;
	$result = 0;
	$fy = floor($fig_pos/8); $fx = $fig_pos%8;
	$dy = floor($dest_pos/8); $dx = $dest_pos%8;
	/* DEBUG:  echo "$fx,$fy --> $dx,$dy: "; */
	switch ( $fig )
	{
		/* knight */
		case 'N':
			if ( abs($fx-$dx)==1 && abs($fy-$dy)==2 )
				$result = 1;
			if ( abs($fy-$dy)==1 && abs($fx-$dx)==2 )
				$result = 1;
			break;
		/* bishop */
		case 'B':
			if ( abs($fx-$dx) != abs($fy-$dy) )
				break;
			if ( $dy < $fy ) $change = -8; else $change =  8;
			if ( $dx < $fx ) $change -= 1; else $change += 1;
			if ( $this->pathIsNotBlocked( $fig_pos+$change, $dest_pos, $change ) )
				$result = 1;
			break;
		/* rook */
		case 'R':
			if ( $fx!=$dx && $fy!=$dy )
				break;
			if ( $fx==$dx )
			{
				if ( $dy<$fy ) $change = -8; else $change = 8;
			}
			else {
				if ( $dx<$fx ) $change = -1; else $change = 1;
			}
			if ( $this->pathIsNotBlocked( $fig_pos+$change, $dest_pos, $change ) )
				$result = 1;
			break;
		/* queen */
		case 'Q':
			if ( abs($fx-$dx) != abs($fy-$dy) && $fx!=$dx && $fy!=$dy )
				break;
			if ( abs($fx-$dx) == abs($fy-$dy) )
			{
				if ( $dy < $fy ) $change = -8; else $change =  8;
				if ( $dx < $fx ) $change -= 1; else $change += 1;
			}
			else if ( $fx==$dx ) {
				if ( $dy<$fy ) $change = -8; else $change = 8;
			}
			else
			{
				if ( $dx<$fx ) $change = -1; else $change = 1;
			}
			if ( $this->pathIsNotBlocked( $fig_pos+$change, $dest_pos, $change ) )
				$result = 1;
			break;
		/* king */
		case 'K':
			if ( abs($fx-$dx) > 1 || abs($fy-$dy) > 1 ) break;
			$kings = 0;
			$adj_tiles = $this->getAdjTiles( $dest_pos );
			foreach( $adj_tiles as $tile )
				if ($this->board[$tile]{1} == 'K' ) $kings++;
			if ( $kings == 2 ) break;
			$result = 1;
			break;
	}

	/* DEBUG: echo " $result<BR>"; */
	return $result;
}

/**
 * Check whether a pawn can attack a tile.
 *
 * @param int $fig_pos Position of pawn
 * @param int $dest_pos Tile to check
 * @return bool True if pawn can attack
 *
 * @access private
 */
function checkPawnAttack($fig_pos, $dest_pos)
{
	if ( $this->board[$fig_pos]{0} == 'w' )
	{
		if ( ($fig_pos % 8) > 0 && $dest_pos == $fig_pos+7 )
			return true;
		if ( ($fig_pos % 8) < 7 && $dest_pos == $fig_pos+9 )
			return true;
	}
	else if ( $this->board[$fig_pos]{0} == 'b' )
	{
		if ( ($fig_pos % 8) < 7 && $dest_pos == $fig_pos-7 )
			return true;
		if ( ($fig_pos % 8) > 0 && $dest_pos == $fig_pos-9 )
			return true;
	}
	return false;
}

/**
 * Check whether a pawn move is legal.
 *
 * check whether pawn at figpos may move to destpos.
 * first move may be two tiles instead of just one.
 * again the last tile is not checked but just the path
 * in between.
 *
 * @param int $fig_pos  Position of pawn.
 * @param int $dest_pos  Destination tile.
 * @return bool True if move is legal
 *
 * @access private
 */
function checkPawnMove($fig_pos, $dest_pos)
{
	$first_move = 0;

	if ( $this->board[$fig_pos]{0} == 'w' )
	{
		if ( $fig_pos >= 8 && $fig_pos <= 15 )
			$first_move = 1;
		if ( $dest_pos==$fig_pos+8 )
			return true;
		if ( $first_move && ( $dest_pos==$fig_pos+16 ) )
		if ($this->is_empty_tile($fig_pos + 8))
			return true;
	}
	else if ($this->board[$fig_pos]{0} == 'b' )
	{
		if ( $fig_pos >= 48 && $fig_pos <= 55 )
			$first_move = 1;
		if ( $dest_pos==$fig_pos-8 )
			return true;
		if ( $first_move && ( $dest_pos==$fig_pos-16 ) )
		if ($this->is_empty_tile($fig_pos - 8))
			return true;
	}
	return false;
}

/**
 * Check whether a tile is under attack by the specified player.
 *
 * @param string $opp  Attacking color ('w' or 'b')
 * @param int $dest_pos  Tile to check
 * @return bool
 *
 * @access private
 */
function tileIsUnderAttack($opp, $dest_pos)
{
#var_dump('tileIsUnderAttack, opp', $opp, 'dest_pos', $dest_pos, 'board', $board);#*#DEBUG#
	for ( $i = 0; $i < 64; $i++ )
		if ($this->board[$i]{0} == $opp )
		{
			if ( ($this->board[$i]{1}=='P' && $this->checkPawnAttack($i,$dest_pos)) ||
					 ($this->board[$i]{1}!='P' &&
								$this->tileIsReachable($this->board[$i]{1},$i,$dest_pos)) )
			{
				/*DEBUG: echo "attack test: $i: ",$opp,"P<BR>"; */
				return true;
			}
		}
	return false;
}

/**
 * Check whether a player's king can be attacked by his opponent.
 *
 * @param string $player  Player's color ('w' or 'b')
 * @param string $opp     Opponent's color ('w' or 'b')
 * @return bool
 *
 * @access private
 */
function kingIsUnderAttack($player, $opp)
{
	#var_dump('kingIsUnderAttack, player', $player, 'opp', $opp, 'board', $this->board);#*#DEBUG#

	for ( $i = 0; $i < 64; $i++ )
		if ($this->board[$i]{0} == $player && $this->board[$i]{1} == 'K' )
		{
			$king_pos = $i;
			break;
		}
	#echo "$player king is at $king_pos<BR>";#*#DEBUG#

	return $this->tileIsUnderAttack( $opp, $king_pos );
}

/**
 * Check whether player's king is checkmated by his opponent.
 *
 * @param string $player  Player's color ('w' or 'b')
 * @param string $opp     Opponent's color ('w' or 'b')
 * @return bool
 *
 * @access private
 */
function isCheckMate($player, $opp)
{
	for ( $i = 0; $i < 64; $i++ )
		if ($this->board[$i]{0} == $player && $this->board[$i]{1} == 'K' )
		{
			$king_pos = $i;
			$king_x = $i % 8;
			$king_y = floor($i/8);
			break;
		}

	/* test adjacent tiles while king is temporarly removed */
	$adj_tiles = $this->getAdjTiles( $king_pos );
	$contents = $this->board[$king_pos]; $this->clear_tile($king_pos);
	foreach ( $adj_tiles as $dest_pos )
	{
		if ($this->board[$dest_pos]{0} == $player ) continue;
		if ( $this->tileIsUnderAttack($opp,$dest_pos) ) continue;
		$this->board[$king_pos] = $contents;
		return false;
	}
	$this->board[$king_pos] = $contents;

	/* DEBUG:  echo "King cannot escape by itself! "; */

	/* get all figures that attack the king */
	$attackers = array(); $count = 0;
	for ( $i = 0; $i < 64; $i++ )
		if ( $this->board[$i]{0} == $opp )
		{
			if ( ($this->board[$i]{1}=='P' && $this->checkPawnAttack($i,$king_pos)) ||
					 ($this->board[$i]{1}!='P' &&
								$this->tileIsReachable($this->board[$i]{1},$i,$king_pos)) )
			{
					$attackers[$count++] = $i;
			}
		}
	/* DEBUG:
	for( $i = 0; $i < $count; $i++ )
		echo "Attacker: $attackers[$i] ";
	echo "Attackercount: ",count($attackers), " "; */

	/* if there are no atackers, it is not checkmate */
	if ( 0 == $count ) return false;

	/* if more than one there is no chance to escape */
	if ( $count > 1 ) return true;

	/* check whether attacker can be killed by own figure */
	$dest_pos = $attackers[0];
	for ( $i = 0; $i < 64; $i++ )
		if ( $this->board[$i]{0} == $player )
		{
			if ( ($this->board[$i]{1}=='P' && $this->checkPawnAttack($i,$dest_pos)) ||
					 ($this->board[$i]{1}!='P' && $this->board[$i]{1}!='K' &&
							$this->tileIsReachable($this->board[$i]{1},$i,$dest_pos)) ||
					 ($this->board[$i]{1}=='K' &&
							$this->tileIsReachable($this->board[$i]{1},$i,$dest_pos) &&
							!$this->tileIsUnderAttack($opp,$dest_pos)) )
			{
				/* DEBUG: echo "candidate: $i "; */
				$can_kill_atk = 0;
				$contents_def = $this->board[$i];
				$contents_atk = $this->board[$dest_pos];
				$this->board[$dest_pos] = $this->board[$i];
				$this->clear_tile($i);
				if ( !$this->tileIsUnderAttack($opp,$king_pos) )
					$can_kill_atk = 1;
				$this->board[$i] = $contents_def;
				$this->board[$dest_pos] = $contents_atk;
				if ( $can_kill_atk )
				{
					/* DEBUG: echo "$i can kill attacker"; */
					return false;
				}
			}
		}

	/* check whether own unit can block the way */

	/* if attacking unit is a knight there
	 * is no way to block the path */
	if ( $this->board[$dest_pos]{1} == 'N' ) return true;

	/* if enemy is adjacent to king there is no
	 * way either */
	$dest_x = $dest_pos % 8;
	$dest_y = floor($dest_pos/8);
	if ( abs($dest_x-$king_x)<=1 && abs($dest_y-$king_y)<=1 )
		return true;

	/* get the list of tiles between king and attacking
	 * unit that can be blocked to stop the attack */
	$change = $this->getPathChange($this->board[$dest_pos]{1},$dest_pos,$king_pos);
	/* DEBUG:  echo "path change: $change "; */
	$path = $this->getPath($dest_pos+$change,$king_pos,$change);
	/* DEBUG: foreach( $path as $tile ) echo "tile: $tile "; */
	foreach( $path as $pos )
	{
		for ( $i = 0; $i < 64; $i++ )
			if ( $this->board[$i]{0} == $player )
			{
				if ( ($this->board[$i]{1}=='P' && $this->checkPawnMove($i,$pos)) ||
						 ($this->board[$i]{1}!='P' && $this->board[$i]{1}!='K' &&
									$this->tileIsReachable($this->board[$i]{1},$i,$pos)) )
				{
						/* DEBUG: echo "$i can block "; */
						return false;
				}
			}
	}
	return true;
}

/**
 * Check whether player is stalemated.
 *
 * @param string $player  Color of player who has the move ('w' or 'b')
 * @param string $opp     Opponent's color ('w' or 'b')
 * @return bool
 *
 * @todo recognize when move is not possible because of check
 *
 * @access private
 */
function isStaleMate($player, $opp)
{
	/* if there is more than one piece for the player, it is not stalemate */
	if (1 < eval("count(\$this->".$player."_figures);"))
		return false;

	for ( $i = 0; $i < 64; $i++ )
		if ($this->board[$i]{0} == $player )
			switch ($this->board[$i]{1} )
			{
				case 'K':
					$adj_tiles = $this->getAdjTiles( $i );
					foreach ( $adj_tiles as $pos )
					{
						if ( $this->board[$pos]{0} == $player ) continue;
						if ( $this->tileIsUnderAttack($opp,$pos) ) continue;
						return false;
					}
					/* DEBUG:  echo "King cannot escape by itself! "; */
					break;
				case 'P':
					if ( $player == 'w' )
					{
						if ($this->is_empty_tile($i + 8)) return false;
						if ( ($i%8) > 0 && $this->board[$i+7]{0} != $player ) return false;
						if ( ($i%8) < 7 && $this->board[$i+9]{0} != $player ) return false;
					}
					else
					{
						if ($this->is_empty_tile($i - 8)) return false;
						if ( ($i%8) > 0 && $this->board[$i-9]{0} != $player ) return false;
						if ( ($i%8) < 7 && $this->board[$i-7]{0} != $player ) return false;
					}
					break;
				case 'B':
					if ( $i-9 >= 0  && $this->board[$i-9]{0} != $player ) return false;
					if ( $i-7 >= 0  && $this->board[$i-7]{0} != $player ) return false;
					if ( $i+9 <= 63 && $this->board[$i+9]{0} != $player ) return false;
					if ( $i+7 <= 63 && $this->board[$i+7]{0} != $player ) return false;
					break;
				case 'R':
					if ( $i-8 >= 0  && $this->board[$i-8]{0} != $player ) return false;
					if ( $i-1 >= 0  && $this->board[$i-1]{0} != $player ) return false;
					if ( $i+8 <= 63 && $this->board[$i+8]{0} != $player ) return false;
					if ( $i+1 <= 63 && $this->board[$i+1]{0} != $player ) return false;
					break;
				case 'Q':
					$adj_tiles = $this->getAdjTiles( $i );
					foreach ( $adj_tiles as $pos )
						if ( $this->board[$pos]{0} != $player )
							return false;
					break;
				case 'N':
					if ( $i-17 >= 0  && $this->board[$i-17]{0} != $player ) return false;
					if ( $i-15 >= 0  && $this->board[$i-15]{0} != $player ) return false;
					if ( $i-6  >= 0  && $this->board[$i-6]{0}  != $player ) return false;
					if ( $i+10 <= 63 && $this->board[$i+10]{0} != $player ) return false;
					if ( $i+17 <= 63 && $this->board[$i+17]{0} != $player ) return false;
					if ( $i+15 <= 63 && $this->board[$i+15]{0} != $player ) return false;
					if ( $i+6  <= 63 && $this->board[$i+6]{0}  != $player ) return false;
					if ( $i-10 >= 0  && $this->board[$i-10]{0} != $player ) return false;
					break;
			}

	return true;
}

/**
 * Generate informational text message with parameters.
 *
 * Example:
 * <pre>
 *   echo move_msg('cannot find {$param[1]} pawn in column {$param[2]}', 'b', 'e');
 *    - prints: "cannot find b pawn in column e"
 * </pre>
 *
 * @param string $text
 * @param string $param,...  Unlimited number of parameters referenced by $text
 * @return string
 *
 * @access private
 */
function move_msg($text)
{
	$param = func_get_args();
	#var_dump('move_msg, text', $text, 'param', $param);#*#DEBUG#
	return eval("return \"$text\";");
}

/**
 * Translate Standard Algebraic Notation (SAN) into a full move description.
 *
 * The completed move is placed in $this->ac_move.
 *
 * @param string $player 'w' or 'b'
 * @param string $move
 * <pre>
 *   [a-h][1-8|a-h][RNBQK]              pawn move/attack
 *   [PRNBQK][a-h][1-8]                 figure move
 *   [PRNBQK][:x][a-h][1-8]             figure attack
 *   [PRNBQK][1-8|a-h][a-h][1-8]        ambigous figure move
 *   [a-h][:x][a-h][1-8][[RNBQK]        ambigous pawn attack
 *   [PRNBQK][1-8|a-h][:x][a-h][1-8]    ambigous figure attack
 * </pre>
 * @return string  Empty string if successful, otherwise error message
 *
 * @access private
 */
function completeMove($player, $move)
{
	/*
	 * [a-h][1-8|a-h][RNBQK]              pawn move/attack
	 * [PRNBQK][a-h][1-8]                 figure move
	 * [PRNBQK][:x][a-h][1-8]             figure attack
	 * [PRNBQK][1-8|a-h][a-h][1-8]        ambigous figure move
	 * [a-h][:x][a-h][1-8][[RNBQK]        ambigous pawn attack
	 * [PRNBQK][1-8|a-h][:x][a-h][1-8]    ambigous figure attack
	 */
	$error = _MD_CHESS_MOVE_UNKNOWN; // "format is totally unknown!"

	$this->ac_move = $move;

	if ( strlen($move)>=6 )
	{
		/* full move: a pawn requires a ? in the end
		 * to automatically choose a queen on last line */
		if ( $move[0] == 'P' )
		if ( $move[strlen($move)-1]<'A' || $move[strlen($move)-1]>'Z' )
			$this->ac_move = "$move?";
		return "";
	}

	/* allow last letter to be a capital one indicating
	 * the chessmen a pawn is supposed to transform into,
	 * when entering the last file. we split this character
	 * to keep the autocompletion process the same. */
	$pawn_upg = "?";
	if ( $move[strlen($move)-1]>='A' && $move[strlen($move)-1]<='Z' )
	{
		$pawn_upg = $move[strlen($move)-1];
		$move = substr( $move, 0, strlen($move)-1 );
	}
	// remove trailing '=', if present
	if ($move{strlen($move)-1} == '=') {
		$move = substr($move, 0, strlen($move)-1);
	}
	if ( $pawn_upg == "P" || $pawn_upg == "K" )
		return _MD_CHESS_MOVE_PAWN_MAY_BECOME; // "A pawn may only become either a knight, a bishop, a rook or a queen!"

	if ( $move[0]>='a' && $move[0]<='h' )
	{
		/* pawn move. either it's 2 or for characters as
		 * listed above */
		if ( strlen($move) == 4 )
		{
			if ( $move[1] != 'x' )
				return _MD_CHESS_MOVE_USE_X; // "use x to indicate an attack"
			$dest_x = $move[2];
			$dest_y = $move[3];
			$src_x  = $move[0];
			if ( $player == 'w' )
				$src_y  = $dest_y-1;
			else
				$src_y  = $dest_y+1;
			$this->ac_move = sprintf( "P%s%dx%s%d%s",
													$src_x,$src_y,$dest_x,$dest_y,
													$pawn_upg );
			return "";
		}
		else
		if (strlen($move) == 2 )
		{
			$fig = sprintf( "%sP", $player );
			if ( $move[1] >= '1' && $move[1] <= '8' )
			{
				/* pawn move */
				$pos = $this->boardCoordToIndex( $move );
				if ( $pos == 64 ) return $this->move_msg(_MD_CHESS_MOVE_COORD_INVALID, $move); // "coordinate $move is invalid"
				if ( $player == 'w' )
				{
					while( $pos >= 0 && $this->board[$pos] != $fig ) $pos -= 8;
					if ( $pos < 0 ) $not_found = 1;
				}
				else
				{
					while( $pos <= 63 && $this->board[$pos] != $fig ) $pos += 8;
					if ( $pos > 63 ) $not_found = 1;
				}
				$pos = $this->boardIndexToCoord( $pos );
				if ( (isset($not_found) && $not_found) || $pos == "" ) {
					return $this->move_msg(_MD_CHESS_MOVE_CANNOT_FIND_PAWN, $player, $move[0]); // "cannot find $player pawn in column $move[0]"
				} else {
					$this->ac_move = sprintf( "P%s-%s%s", $pos, $move, $pawn_upg );
					return "";
				}
			}
			else
			{
				/* notation: [a-h][a-h] for pawn attack no longer allowed
				 * except for history browser */
				if ( $this->browsing_mode == 0 )
						return _MD_CHESS_MOVE_USE_NOTATION; // "please use denotation [a-h]x[a-h][1-8] for pawn attacks (see help for more information)"
				/* pawn attack must be only one pawn in column! */
				$pawns = 0;
				$start = $this->boardCoordToIndex( sprintf( "%s1", $move[0] ) );
				if ( $start == 64 ) return $this->move_msg(_MD_CHESS_MOVE_COORD_INVALID, $move[0]); // "coordinate $move[0] is invalid"
				for ( $i = 1; $i <= 8; $i++, $start+=8 )
					if ( $this->board[$start] == $fig )
					{
						$pawns++;
						$pawn_line = $i;
					}
				if ( $pawns == 0 )
					return $this->move_msg(_MD_CHESS_MOVE_NO_PAWN, $move[0]); // "there is no pawn in column $move[0]"
				else if ( $pawns > 1 )
					return $this->move_msg(_MD_CHESS_MOVE_TWO_PAWNS, $move[0]); // "there is more than one pawn in column $move[0]"
				else
				{
					if ( $player == 'w' )
						$dest_line = $pawn_line+1;
					else
						$dest_line = $pawn_line-1;
					$this->ac_move = sprintf( "P%s%dx%s%d",
														$move[0],$pawn_line,$move[1],$dest_line );
					return "";
				}
			}
		}
	}
	else
	{
		/* figure move */
		$dest_coord = substr( $move, strlen($move)-2, 2 );
		$action = $move[strlen($move)-3];
		if ( $action != 'x' ) $action = '-';
		if ( $player == 'w' )
			$figures = $this->w_figures;
		else
			$figures = $this->b_figures;
		$fig_count = 0;
		foreach( $figures as $figure )
			if ( $figure[0] == $move[0] )
			{
				$fig_count++;
				if ( $fig_count == 1 )
					$pos1 = substr( $figure, 1, 2 );
				else
					$pos2 = substr( $figure, 1, 2 );
			}
		if ( $fig_count == 0 )
			return $this->move_msg(_MD_CHESS_MOVE_NO_FIGURE, $move[0], $this->getFullFigureName($move[0])); // sprintf("there is no figure %s = %s", $move[0], $this->getFullFigureName($move[0]))
		else
		if ( $fig_count == 1 )
		{
			 $this->ac_move = sprintf( "%s%s%s%s",
											$move[0], $pos1, $action, $dest_coord );
			 return "";
		}
		else
		{
			/* two figures which may cause ambiguity */
			$dest_pos = $this->boardCoordToIndex( $dest_coord );
			if ( $dest_pos == 64 )
				return $this->move_msg(_MD_CHESS_MOVE_COORD_INVALID, $dest_coord); // "coordinate $dest_coord is invalid"
			$fig1_can_reach = $this->tileIsReachable( $move[0],
														$this->boardCoordToIndex($pos1), $dest_pos );
			$fig2_can_reach = $this->tileIsReachable( $move[0],
														$this->boardCoordToIndex($pos2), $dest_pos );
			if ( !$fig1_can_reach && !$fig2_can_reach )
				return $this->move_msg(_MD_CHESS_MOVE_NEITHER_CAN_REACH, $move[0], $this->getFullFigureName($move[0]), $dest_coord); // sprintf("neither of the %s = %s can reach %s", $move[0], $this->getFullFigureName($move[0]), $dest_coord)
			else
			if ( $fig1_can_reach && $fig2_can_reach )
			{
				/* ambiguity - check whether a hint is given */
				if ( ($action=='-' && strlen($move)==4) ||
						 ($action=='x' && strlen($move)==5) )
					$hint = $move[1];
				if ( empty($hint) )
					return $this->move_msg(_MD_CHESS_MOVE_BOTH_CAN_REACH, $move[0], $this->getFullFigureName($move[0]), $dest_coord); // sprintf("both of the %s = %s can reach %s", $move[0], $this->getFullFigureName($move[0]), $dest_coord)
				else
				{
					$move_fig1 = 0;
					$move_fig2 = 0;
					if ( $hint>='1' && $hint<='8' )
					{
						if ( $pos1[1]==$hint && $pos2[1]!=$hint )
							$move_fig1 = 1;
						if ( $pos2[1]==$hint && $pos1[1]!=$hint )
							$move_fig2 = 1;
					}
					else
					{
						if ( $pos1[0]==$hint && $pos2[0]!=$hint )
							$move_fig1 = 1;
						if ( $pos2[0]==$hint && $pos1[0]!=$hint )
							$move_fig2 = 1;
					}
					if ( !$move_fig1 && !$move_fig2 )
						return _MD_CHESS_MOVE_AMBIGUOUS; // "ambiguity is not properly resolved"
					if ( $move_fig1 )
						$this->ac_move = sprintf( "%s%s%s%s",
													$move[0], $pos1, $action, $dest_coord );
					else
						$this->ac_move = sprintf( "%s%s%s%s",
													$move[0], $pos2, $action, $dest_coord );
					return;
				}
			}
			else
			{
				if ( $fig1_can_reach )
					$this->ac_move = sprintf( "%s%s%s%s",
												$move[0], $pos1, $action, $dest_coord );
				else
					$this->ac_move = sprintf( "%s%s%s%s",
												$move[0], $pos2, $action, $dest_coord );
				return "";
			}
		}
	}

	return $error;
}

/**
 * A hacky function that uses autocomplete to short
 * a full move. if this fails there is no warning
 * but the move is kept unchanged.
 *
 * @param string $player  'w' or 'b'
 * @param string $move
 * @return string  new move
 *
 * @access private
 */
function convertFullToChessNotation($player, $move)
{
	$new_move = $move;

	$old_ac_move = $this->ac_move; /* backup required as autocomplete
															will overwrite it */

	/* valid pawn moves are always non-ambigious */
	if ( $move[0] == 'P' )
	{
		/* skip P anycase. for attacks skip source digit
			 and for moves skip source pos and - */
		if ( $move[3] == '-' )
			$new_move = substr( $move, 4 );
		else
		if ( $move[3] == 'x' )
			$new_move = sprintf("%s%s", $move[1], substr( $move, 3 ) );
	}
	else
	{
		/* try to remove the source position and check whether it
		 * is a non-ambigious move. if it is add one of the components
		 * and check again */
		if ( $move[3] == '-' )
			$dest = substr( $move, 4 );
		else
		if ( $move[3] == 'x' )
			$dest = substr( $move, 3 );
		$new_move = sprintf("%s%s", $move[0], $dest );
		if ( $this->completeMove($player,$new_move) != "" )
		{
			/* add a component */
			$new_move = sprintf("%s%s%s", $move[0], $move[1], $dest );
			if ( $this->completeMove($player,$new_move) != "" )
			{
				/* add other component */
				$new_move = sprintf("%s%s%s", $move[0], $move[2], $dest );
				if ( $this->completeMove($player,$new_move) != "" )
					 $new_move = $move; /* give up */
			}
		}
	}

	$this->ac_move = $old_ac_move;
	return $new_move;
}

/**
 * Handle a move.
 *
 * check whether it is user's turn and the move is valid.
 * if the move is okay update the game file.
 *
 * @param string $move
 * @return array A two-element array:
 *  - $move_performed: true if the move was performed and the game state has been updated, false otherwise
 *  - $move_result_text: text message
 *
 * @access private
 */
function handleMove($move)
{
	/* DEBUG: echo "HANDLE: $move, $comment<BR>"; */

	$result = _MD_CHESS_MOVE_UNDEFINED;
	$move_handled = 0;

	// Use $this->gamestate['fen_piece_placement'] to initialize $this->board, $this->w_figures and $this->b_figures.
	$this->fen_piece_placement_to_board() or trigger_error('handleMove, piece_placement invalid', E_USER_ERROR);

	// get color of current player
	$cur_player = $this->gamestate['fen_active_color']; /* b or w */

	if ($cur_player != 'w' and $cur_player != 'b') {
		return(array(false, "handleMove, internal error: player='$cur_player'"));
	}

	$cur_opp = ($cur_player == 'w') ? 'b' : 'w';

	if ($this->gamestate['pgn_result'] != '*')
	{
		return(array(false, _MD_CHESS_MOVE_GAME_OVER));
	}

	// get castling availability flags
	$white_may_castle_short = strstr($this->gamestate['fen_castling_availability'], 'K') ? true : false;
	$white_may_castle_long  = strstr($this->gamestate['fen_castling_availability'], 'Q') ? true : false;
	$black_may_castle_short = strstr($this->gamestate['fen_castling_availability'], 'k') ? true : false;
	$black_may_castle_long  = strstr($this->gamestate['fen_castling_availability'], 'q') ? true : false;

	// Castling is supposed to use ohs, not zeros.  Allow zeros on input anyway.
	if ($move == '0-0') {
		$move = 'O-O';
	} elseif ($move == '0-0-0') {
		$move = 'O-O-O';
	}

	// allow two-step of king to indicate castling
	if ($cur_player == 'w' && $move == 'Ke1-g1') {
		$move = 'O-O';
	} elseif ($cur_player == 'w' && $move == 'Ke1-c1') {
		$move = 'O-O-O';
	} elseif ($cur_player == 'b' && $move == 'Ke8-g8') {
		$move = 'O-O';
	} elseif ($cur_player == 'b' && $move == 'Ke8-c8') {
		$move = 'O-O-O';
	}

	/* backup full move input for game history before
	 * splitting figure type apart */
	$history_move = $move;

	/* clear last move - won't be saved yet if anything
		 goes wrong */
	$this->last_move = "x";
	$this->piece_captured = 'x';

	/* HANDLE MOVES:
	 * ---                               surrender
	 * O-O                               short castling
	 * O-O-O                             long castling
	 * [PRNBQK][a-h][1-8][-:x][a-h][1-8] unshortened move
	 */
	if ($move == 'O-O') {

		/* short castling */

		if ($cur_player=="b") {

			if (!$black_may_castle_short) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot castle short any longer!
			}
			if (!$this->is_empty_tile(61) or !$this->is_empty_tile(62)) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // Cannot castle short because the way is blocked!
			}
			if ($this->kingIsUnderAttack( "b", "w" )) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot escape check by castling!
			}
			if ( $this->tileIsUnderAttack( "w", 62 ) || $this->tileIsUnderAttack( "w", 61 ) ) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // A tile the king passes over or into would be under attack after short castling!
			}
			$this->clear_tile(60);
			$this->board[62] = "bK";
			$this->board[61] = "bR";
			$this->clear_tile(63);
			$black_may_castle_short = false;
			$black_may_castle_long  = false;

		} else {

			if (!$white_may_castle_short) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot castle short any longer!
			}
			if (!$this->is_empty_tile(5) or !$this->is_empty_tile(6)) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // Cannot castle short because the way is blocked!
			}
			if ( $this->kingIsUnderAttack( "w", "b" ) ) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot escape check by castling!
			}
			if ( $this->tileIsUnderAttack( "b", 5 ) || $this->tileIsUnderAttack( "b", 6 ) ) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // A tile the king passes over or into would be under attack after short castling!
			}
			$this->clear_tile(4);
			$this->board[6] = "wK";
			$this->board[5] = "wR";
			$this->clear_tile(7);
			$white_may_castle_short = false;
			$white_may_castle_long  = false;
		}
		$result = _MD_CHESS_MOVE_CASTLED_SHORT;
		$move_handled = 1;
		$this->last_move = "O-O";

	} elseif ($move == 'O-O-O') {

		/* long castling */

		if ($cur_player=="b") {

			if (!$black_may_castle_long) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot castle long any longer!
			}
			if (!$this->is_empty_tile(57) or !$this->is_empty_tile(58) or !$this->is_empty_tile(59)) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // Cannot castle long because the way is blocked!
			}
			if ( $this->kingIsUnderAttack( "b", "w" ) ) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot escape check by castling!
			}
			if ( $this->tileIsUnderAttack( "w", 58 ) || $this->tileIsUnderAttack( "w", 59 ) ) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // A tile the king passes over or into would be under attack after short castling!
			}
			$this->clear_tile(56);
			$this->board[58] = "bK";
			$this->board[59] = "bR";
			$this->clear_tile(60);
			$black_may_castle_short = false;
			$black_may_castle_long  = false;

		} else {

			if (!$white_may_castle_long) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot castle long any longer!
			}
			if (!$this->is_empty_tile(1) or !$this->is_empty_tile(2) or !$this->is_empty_tile(3)) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // Cannot castle long because the way is blocked!
			}
			if ($this->kingIsUnderAttack( "w", "b" )) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // You cannot escape check by castling!
			}
			if ( $this->tileIsUnderAttack( "b", 2 ) || $this->tileIsUnderAttack( "b", 3 ) ) {
				return(array(false, _MD_CHESS_MOVE_NO_CASTLE)); // A tile the king passes over or into would be under attack after short castling!
			}
			$this->clear_tile(0);
			$this->board[2] = "wK";
			$this->board[3] = "wR";
			$this->clear_tile(4);
			$white_may_castle_short = false;
			$white_may_castle_long  = false;
		}
		$result = _MD_CHESS_MOVE_CASTLED_LONG;
		$move_handled = 1;
		$this->last_move = "O-O-O";
	}

	else
	{
		/* [PRNBQK][a-h][1-8][-:x][a-h][1-8][RNBQK] full move */

		/* allow short move description by autocompleting to
		 * full description */
		$ac_error = $this->completeMove( $cur_player, trim($move) );
		if ( $ac_error != "" )
			return(array(false, $ac_error)); // "ERROR: autocomplete: $ac_error"
		else
			$move = $this->ac_move;
		$this->last_move = str_replace( "?", "", $move );

		/* a final captial letter may only be N,B,R,Q for the
		 * appropiate chessman */
		$c = $move[strlen($move)-1];
		if ( $c >= 'A' && $c <= 'Z' )
		if ( $c != 'N' && $c != 'B' && $c != 'R' && $c != 'Q' )
			return(array(false, _MD_CHESS_MOVE_INVALID_PIECE)); // "ERROR: only N (knight), B (bishop), R (rook) and Q (queen) are valid chessman identifiers"

		/* if it is a full move, try to shorten the history move */
		if ( strlen( $history_move ) >= 6 )
			$history_move =
					$this->convertFullToChessNotation($cur_player,$history_move);
		/* DEBUG: echo "Move: $move ($history_move)<BR>"; */

		/* validate figure and position */
		$fig_type = $move[0];
		$fig_name = $this->getFullFigureName( $fig_type );
		if ( $fig_name == "empty" )
			return(array(false, $this->move_msg(_MD_CHESS_MOVE_UNKNOWN_FIGURE, $fig_type))); // "ERROR: Figure $fig_type is unknown!"
		$fig_coord = substr($move,1,2);
		$fig_pos = $this->boardCoordToIndex( $fig_coord );
		if ( $fig_pos == 64 ) return(array(false, $this->move_msg(_MD_CHESS_MOVE_COORD_INVALID, $fig_coord))); // "ERROR: $fig_coord is invalid!"
		/* DEBUG  echo "fig_type: $fig_type, fig_pos: $fig_pos<BR>"; */
		if ($this->is_empty_tile($fig_pos))
			return(array(false, $this->move_msg(_MD_CHESS_MOVE_TILE_EMPTY, $fig_coord))); // "ERROR: Tile $fig_coord is empty."
		if ( $this->board[$fig_pos]{0} != $cur_player )
			return(array(false, _MD_CHESS_MOVE_NOT_YOUR_PIECE)); // "ERROR: Figure does not belong to you!"
		if ( $this->board[$fig_pos]{1} != $fig_type )
			return(array(false, _MD_CHESS_MOVE_NOEXIST_FIGURE)); // "ERROR: Figure does not exist!"

		/* get target index */
		$dest_coord = substr($move,4,2);
		$dest_pos = $this->boardCoordToIndex( $dest_coord );
		if ( $dest_pos == 64 )
			return(array(false, $this->move_msg(_MD_CHESS_MOVE_COORD_INVALID, $dest_coord))); // "ERROR: $dest_coord is invalid!"
		if ( $dest_pos == $fig_pos )
			return(array(false, _MD_CHESS_MOVE_START_END_SAME));
		/* DEBUG  echo "dest_pos: $dest_pos<BR>"; */

		/* get action */
		$action = $move[3];
		if ( $move[3] == "-" )
			$action = 'M'; /* move */
		else if ( $move[3] == 'x' )
			$action = 'A'; /* attack */
		else
			return(array(false, $this->move_msg(_MD_CHESS_MOVE_UNKNOWN_ACTION, $action))); // "ERROR: $action is unknown! Please use \"-\" for a move and \"x\" for an attack."

		/* if attack an enemy unit must be present on tile
		 * and if move then tile must be empty. in both cases
		 * the king must not be checked after moving. */

		/* check whether the move is along a valid path and
		 * whether all tiles in between are empty thus the path
		 * is not blocked. the final destination tile is not
		 * checked here. */
		if ( $fig_type != 'P' )
		{
				if ( !$this->tileIsReachable( $fig_type, $fig_pos, $dest_pos ) )
					return(array(false, $this->move_msg(_MD_CHESS_MOVE_OUT_OF_RANGE, $dest_coord, $fig_name, $fig_coord))); // "ERROR: Tile $dest_coord is out of moving range for $fig_name at $fig_coord!"
		}
		else {
			if ( $action == 'M' && !$this->checkPawnMove( $fig_pos, $dest_pos ) )
					return(array(false, $this->move_msg(_MD_CHESS_MOVE_OUT_OF_RANGE, $dest_coord, $fig_name, $fig_coord))); // "ERROR: Tile $dest_coord is out of moving range for $fig_name at $fig_coord!"
			if ( $action == 'A' && !$this->checkPawnAttack( $fig_pos, $dest_pos ) )
					return(array(false, $this->move_msg(_MD_CHESS_MOVE_OUT_OF_RANGE, $dest_coord, $fig_name, $fig_coord))); // "ERROR: Tile $dest_coord is out of attacking range for $fig_name at $fig_coord!"
		}

	$en_passant_capture_performed = 0; // 1 if en passant captured occurred, else 0
	/* check action */
	if ( $action == 'M' && !$this->is_empty_tile($dest_pos)) {
		return(array(false, $this->move_msg(_MD_CHESS_MOVE_OCCUPIED, $dest_coord))); // "ERROR: Tile $dest_coord is occupied. You cannot move there."
	}
	if ( $action == 'A' && $this->is_empty_tile($dest_pos)) {
		/* en passant of pawn? */
		if ( $fig_type == 'P' ) {
			if ($this->boardIndexToCoord($dest_pos) == $this->gamestate['fen_en_passant_target_square']) {
				$en_passant_capture_performed = 1;
			}
			if ($en_passant_capture_performed == 0) {
				return(array(false, _MD_CHESS_MOVE_NO_EN_PASSANT)); // "ERROR: en-passant no longer possible!"
			}
		} else {
			return(array(false, $this->move_msg(_MD_CHESS_MOVE_ATTACK_EMPTY, $dest_coord))); // "ERROR: Tile $dest_coord is empty. You cannot attack it."
		}
	}
	if ( $action == 'A' && $this->board[$dest_pos]{0} == $cur_player ) {
		return(array(false, $this->move_msg(_MD_CHESS_MOVE_ATTACK_SELF, $dest_coord))); // "ERROR: You cannot attack own unit at $dest_coord."
	}

		/* backup affected tiles */
		$old_fig_tile = $this->board[$fig_pos];
		$old_dest_tile = $this->board[$dest_pos];

		/* perform move */
		$this->clear_tile($fig_pos);
		if (!$this->is_empty_tile($dest_pos))
			$this->piece_captured = sprintf("%s%s",$this->board[$dest_pos],$dest_pos);
		$this->board[$dest_pos] = "$cur_player$fig_type";
		if ( $en_passant_capture_performed ) {
			/* kill pawn */
			if ( $cur_player == 'w' )
			{
				$this->clear_tile($dest_pos - 8);
				$this->piece_captured = sprintf("bP%s",$dest_pos-8);
			}
			else
			{
				$this->clear_tile($dest_pos + 8);
				$this->piece_captured = sprintf("wP%s",$dest_pos+8);
			}
		}

		/* check check :) */
		if ( $this->kingIsUnderAttack( $cur_player, $cur_opp ) )
		{
			$this->board[$fig_pos] = $old_fig_tile;
			$this->board[$dest_pos] = $old_dest_tile;
			if ( $en_passant_capture_performed ) {
			 // restore pawn that was captured above, since that move is invalid
				if ( $cur_player == 'w' )
					$this->board[$dest_pos-8] = "bP";
				else
					$this->board[$dest_pos+8] = "wP";
			}
			return(array(false, _MD_CHESS_MOVE_IN_CHECK)); // "ERROR: Move is invalid because king would be under attack then."
		}

	// check whether this forbids any castling
	if ($fig_type == 'K') {
		if ($cur_player == 'w') {
			$white_may_castle_short = false;
			$white_may_castle_long  = false;
		} else {
			$black_may_castle_short = false;
			$black_may_castle_long  = false;
		}
	}

	if ($fig_type == 'R') {
		if ($cur_player == 'w') {
			if ($fig_pos == 7) {
				$white_may_castle_short = false;
			} elseif ($fig_pos == 0) {
				$white_may_castle_long = false;
			}
		} else {
			if ($fig_pos == 63) {
				$black_may_castle_short = false;
			} elseif ($fig_pos == 56) {
				$black_may_castle_long = false;
			}
		}
	}

	// if a pawn moved two tiles, this will allow 'en passant' on next move
	if ($fig_type == 'P' and abs($fig_pos - $dest_pos) == 16) {
		$file_chars = 'abcdefgh';
		$this->gamestate['fen_en_passant_target_square'] = $file_chars{$fig_pos % 8} . ($cur_player == 'w' ? '3' : '6');
	} else {
		// clear 'en passant'
		$this->gamestate['fen_en_passant_target_square'] = '-';
	}

		if ($action == 'M' )
			$result = $this->move_msg(_MD_CHESS_MOVE_MOVED, $fig_name, $fig_coord, $dest_coord);
		else
			$result = $this->move_msg(_MD_CHESS_MOVE_CAPTURED, $fig_name, $dest_coord, $fig_coord);

		/* if pawn reached last line convert into a queen */
		if ( $fig_type == 'P' )
		{
			if ( ($cur_player=='w' && $dest_pos>= 56) ||
					 ($cur_player=='b' && $dest_pos<= 7 ) )
			{
				$pawn_upg = $move[strlen($move)-1];
				if ( $pawn_upg == '?' )
				{
					$pawn_upg = 'Q';
					$history_move = sprintf( "%sQ", $history_move );
				}
				$this->board[$dest_pos] = "$cur_player$pawn_upg";
				$result .= ' ... ' . $this->move_msg(_MD_CHESS_MOVE_PROMOTED, $this->getFullFigureName($pawn_upg));
			}
		}

		$move_handled = 1;
	}

	/* if a legal move was performed test whether you
	 * check the opponent or even check-mate him. then
	 * update castling and en-passant flags, select the
	 * next player and add the move to the history. */

	if ($move_handled) {

		// Use $this->board to update $this->gamestate['fen_piece_placement'].
		$this->board_to_fen_piece_placement();

		// handle check, checkmate and stalemate
		$comment = '';
		if ($this->kingIsUnderAttack( $cur_opp, $cur_player)) {
			// if this is check mate finish the game, otherwise if not just add a + to the move
			if ($this->isCheckMate($cur_opp, $cur_player)) {
				$this->gamestate['pgn_result'] = $cur_player == 'w' ? '1-0' : '0-1';
				$history_move .= '#';
				$result .= ' ... ' . _MD_CHESS_MOVE_CHECKMATE;
			} else {
				$history_move .= '+';
			}
		} elseif ($this->isStaleMate($cur_opp, $cur_player)) {
			$this->gamestate['pgn_result'] = '1/2-1/2';
			$result .= ' ... ' . _MD_CHESS_MOVE_STALEMATE;
			$comment = _MD_CHESS_DRAW_STALEMATE;
		} elseif ($this->insufficient_mating_material()) {
			$this->gamestate['pgn_result'] = '1/2-1/2';
			$result .= ' ... ' . _MD_CHESS_MOVE_MATERIAL;
			$comment = _MD_CHESS_DRAW_NO_MATE;
		}

		// store possible castling-availability modification
		$KQkq = ($white_may_castle_short ? 'K' : '') . ($white_may_castle_long ? 'Q' : '')
				. ($black_may_castle_short ? 'k' : '') . ($black_may_castle_long ? 'q' : '');
		$this->gamestate['fen_castling_availability'] = !empty($KQkq) ? $KQkq : '-';

		// strip old result-string from end of movetext
		// It's assumed that the movetext for a game in play is terminated by a '*', possibly preceded by whitespace.
		// (The whitespace will be present unless there are no moves in the game yet.)
		$this->gamestate['pgn_movetext'] = preg_replace('/\s*\*$/', '', $this->gamestate['pgn_movetext']);

		// if white move, output move-number
		if ($this->gamestate['fen_active_color'] == 'w') {
			if (!empty($this->gamestate['pgn_movetext'])) {
				$this->gamestate['pgn_movetext'] .= ' ';
			}
			$this->gamestate['pgn_movetext'] .= $this->gamestate['fen_fullmove_number'] . '.';

		// if black move, no moves yet, and game is setup, output move number with special '...' terminator
		} elseif (empty($this->gamestate['pgn_movetext']) and !empty($this->gamestate['pgn_fen'])) {
			$this->gamestate['pgn_movetext'] .= $this->gamestate['fen_fullmove_number'] . '...';
		}

		// update movetext
		// comment is added only for a concluded game
		$this->gamestate['pgn_movetext'] .= ' ' . $history_move . ' ' . $this->gamestate['pgn_result'];
		if (!empty($comment)) {
			$this->gamestate['pgn_movetext'] .= ' {' . $comment . '}';
		}

		// if black move, increment move-number
		if ($this->gamestate['fen_active_color'] == 'b') {
			++$this->gamestate['fen_fullmove_number'];
		}

		// If pawn advance or capturing move, reset the halfmove clock. Otherwise increment it.
		if ($move != 'O-O' and $move != 'O-O-O' and ($move{0} == 'P' or $move{3} == 'x')) {
			$this->gamestate['fen_halfmove_clock'] = 0;
		} else {
			++$this->gamestate['fen_halfmove_clock'];
		}

		// set next player
		$this->gamestate['fen_active_color'] = $this->gamestate['fen_active_color'] == 'w' ? 'b' : 'w';
	}

	return array($move_handled, $result);
}

/**
 * Check whether a tile is empty.
 *
 * @param int $position
 * @return bool
 *
 * @access private
 */
function is_empty_tile($position)
{
	return $this->board[$position] == '00';
}

/**
 * Clear a tile.
 *
 * @param int $position  Position of tile
*
 * @access private
 */
function clear_tile($position)
{
	$this->board[$position] = '00';
}

/**
 * Convert FEN piece placement field to array.
 *
 * Use $this->gamestate['fen_piece_placement'] to initialize $this->board, $this->w_figures and $this->b_figures.
 *
 * @return bool True if piece placement is valid, otherwise false.
 *
 * @access private
 */
function fen_piece_placement_to_board()
{
	if (empty($this->gamestate['fen_piece_placement']) or strlen($this->gamestate['fen_piece_placement']) > 71) {
		#trigger_error('piece placement empty or length invalid', E_USER_WARNING); #*#DEBUG#
		return false; // invalid length
	}

	$this->board = array();
	$piece_map = array(
		'K' => 'wK', 'Q' => 'wQ', 'R' => 'wR', 'B' => 'wB', 'N' => 'wN', 'P' => 'wP',
		'k' => 'bK', 'q' => 'bQ', 'r' => 'bR', 'b' => 'bB', 'n' => 'bN', 'p' => 'bP',
	);
	$tiles = implode('', array_reverse(explode('/', $this->gamestate['fen_piece_placement'])));
	for ($i = 0; $i < strlen($tiles); ++$i) {
		$tile = $tiles{$i};
		if (isset($piece_map[$tile])) {
			$this->board[] = $piece_map[$tile];
		} elseif (is_numeric($tile) and $tile >= 1 and $tile <= 8) {
			for ($j = 0; $j < $tile; ++$j) {
				$this->board[] = '00';
			}
		} else {
			#trigger_error("tile='$tile'", E_USER_WARNING); #*#DEBUG#
			return false; // unexpected character in piece_placement
		}
	}
	if (count($this->board) != 64) {
		#trigger_error('count(board)=' . count($this->board), E_USER_WARNING); #*#DEBUG#
		return false; // piece_placement has incorrect number of tiles
	}

	$this->w_figures = array();
	$this->b_figures = array();
	for ($i = 0; $i < 64; ++$i) {
		$tile = $this->board[$i];
		$coordinates = $this->boardIndexToCoord($i);
		if ($tile{0} == 'w') {
			$this->w_figures[] = $tile{1} . $coordinates;
		} elseif ($tile{0} == 'b') {
			$this->b_figures[] = $tile{1} . $coordinates;
		}
	}

	return true;
}

/**
 * Convert array to FEN piece placement field.
 *
 * Use $this->board to initialize $this->gamestate['fen_piece_placement'].
 *
 * @access private
 */
function board_to_fen_piece_placement()
{
	$rows = array();
	for ($rank = 7; $rank >= 0; --$rank) {
		$row = '';
		for ($file = 0; $file < 8; ++$file) {
			$index = 8 * $rank + $file;
			if (!$this->is_empty_tile($index)) {
				$tile = $this->board[$index];
				$piece = ($tile[0] == 'w') ? $tile[1] : strtolower($tile[1]); // 'K','Q','R','B','N' or 'P' (uppercase for white, lowercase for black)
			} else {
				$piece = 'x'; // temporarily mark each empty tile with 'x'
			}
			$row .= $piece; // append piece symbol to row-string
		}
		$rows[] = $row;
	}
	// Concatenate the eight row-strings with the separator '/'.
	// Then replace each string of x's with the string length.
	$this->gamestate['fen_piece_placement'] = preg_replace_callback('/(x+)/', create_function('$matches','return strlen($matches[1]);'), implode('/', $rows));
}

/**
 * Determine whether there is insufficient material to mate.
 *
 * @return bool  True if only the following pieces remain: K vs. K, K vs. K+B or K vs. K+N; otherwise false.
 *
 * @access private
 */
function insufficient_mating_material()
{
	$pieces      = strtoupper($this->gamestate['fen_piece_placement']);
	$counts      = count_chars($pieces, 1);
	$num_queens  = intval(@$counts[ord('Q')]);
	$num_rooks   = intval(@$counts[ord('R')]);
	$num_bishops = intval(@$counts[ord('B')]);
	$num_knights = intval(@$counts[ord('N')]);
	$num_pawns   = intval(@$counts[ord('P')]);
	return ($num_queens == 0 and $num_rooks == 0 and ($num_bishops + $num_knights) <= 1 and $num_pawns == 0);
}


/**
 * Returns a number based on current status of the game
 *
 * @param string Players color ('w' or 'b')
 * @return int  0 = Normal; 1 = Mate; 2 = Stalemate; 3 = Check
 *
 * @access private
 */
function get_status($player)
{
	$opp = ('w' == $player) ? 'b' : 'w';

	$status = 0;

	if ($this->kingIsUnderAttack($player, $opp))
	{
		$status = 3;

		if ($this->isCheckMate($player, $opp))
		{
			$status = 1;
		}
	}
	else if ($this->isStaleMate($player, $opp))
	{
		$status = 2;
	}

	return $status;
}


function get_status_string($player)
{
	$i = $this->get_status($player);

	switch ($i)
	{
		case gsMate      : return 'Mate';
		case gsStalemate : return 'Stalemate';
		case gsCheck     : return 'Check';
		default          : return '';
	}
}


// --------------------------
// INCIDENTAL PRIVATE METHODS
// --------------------------
// These functions don't really need to be class methods, since they don't access class properties.
// They're placed within the class only for name-scoping.

/**
 * Convert board coordinates [a-h][1-8] to board index [0..63]
 *
 * @param string $coord  Example: 'b3'
 * @return int  Example: 17
 *
 * @access private
 */
function boardCoordToIndex( $coord )
{
	//echo $coord," --> ";
	switch ( $coord[0] )
	{
		case 'a': $x = 0; break;
		case 'b': $x = 1; break;
		case 'c': $x = 2; break;
		case 'd': $x = 3; break;
		case 'e': $x = 4; break;
		case 'f': $x = 5; break;
		case 'g': $x = 6; break;
		case 'h': $x = 7; break;
		default: return 64; /* erronous coord */
	}
	$y = $coord[1]-1;
	if ( $y < 0 || $y > 7 )
		return 64; /* erronous coord */
	$index = $y * 8 + $x;
	//echo "$index | ";
	return $index;
}

/**
 * Convert board index [0..63] to board coordinates [a-h][1-8].
 *
 * @param int $index  Example: 17
 * @return string  Example: 'b3'
 *
 * @access private
 */
function boardIndexToCoord( $index )
{
	//echo $index," --> ";
	if ( $index < 0 || $index > 63 )
		return "";
	$y = floor($index/8)+1;
	$x = chr( ($index%8)+97 );
	$coord = "$x$y";
	//echo "$coord | ";
	return $coord;
}

/**
 * Get piece name from piece symbol.
 *
 * @param string $short  Piece symbol
 * @return string        Piece name
 *
 * @access private
 */
function getFullFigureName( $short )
{
	static $names = array(
		'K' => _MD_CHESS_MOVE_KING,
		'Q' => _MD_CHESS_MOVE_QUEEN,
		'R' => _MD_CHESS_MOVE_ROOK,
		'B' => _MD_CHESS_MOVE_BISHOP,
		'N' => _MD_CHESS_MOVE_KNIGHT,
		'P' => _MD_CHESS_MOVE_PAWN
	);
	return isset($names[$short]) ? $names[$short] : _MD_CHESS_MOVE_EMPTY;
}

/**
 * Get tiles adjacent to specified tile.
 *
 * @param int $fig_pos
 * @return array
 *
 * @access private
 */
function getAdjTiles( $fig_pos )
{
	$adj_tiles = array(); $i = 0;

	$x = $fig_pos % 8; $y = floor( $fig_pos / 8 );

	if ( $x > 0 && $y > 0 ) $adj_tiles[$i++] = $fig_pos-9;
	if (           $y > 0 ) $adj_tiles[$i++] = $fig_pos-8;
	if ( $x < 7 && $y > 0 ) $adj_tiles[$i++] = $fig_pos-7;
	if ( $x < 7           ) $adj_tiles[$i++] = $fig_pos+1;
	if ( $x < 7 && $y < 7 ) $adj_tiles[$i++] = $fig_pos+9;
	if (           $y < 7 ) $adj_tiles[$i++] = $fig_pos+8;
	if ( $x > 0 && $y < 7 ) $adj_tiles[$i++] = $fig_pos+7;
	if ( $x > 0           ) $adj_tiles[$i++] = $fig_pos-1;

	/* DEBUG:  foreach( $adj_tiles as $tile )
		echo "adj: $tile "; */

	return $adj_tiles;
}

} // class ChessGame

?>
