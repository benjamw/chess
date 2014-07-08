<?php

// set the session cookie parameters so the cookie is only valid for this game
$parts = pathinfo($_SERVER['REQUEST_URI']);

$path = $parts['dirname'];
if (empty($parts['extension'])) {
	$path .= $parts['basename'];
}
$path = str_replace('\\', '/', $path).'/';

session_set_cookie_params(0, $path);
@session_start( );

/* load settings */
require_once 'config.inc.php';

/* define constants */
require_once 'chessconstants.inc.php';

/* include outside functions */
require_once 'chessutils.inc.php';
require_once 'gui.inc.php';
require_once 'chessdb.inc.php';

// make sure the error output is turned off, even if testing
ini_set('display_errors', 'Off');

/* load game */
$output_file = "WebChess2_Game_{$_SESSION['game_id']}.pgn";

header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP 1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Content-Transfer-Encoding: none');
header('Content-Type: application/x-chess-pgn; name="'. $output_file . '"');
header('Content-Disposition: attachment; filename="' .$output_file . '"');

// get the FEN data from the database
$i = 0;
$query = "
	SELECT h_fen
	FROM ".T_HISTORY."
	WHERE h_game_id = '{$_SESSION['game_id']}'
	ORDER BY h_time
";
$result = $mysql->fetch_value_array($query, __LINE__, __FILE__);

foreach ($result as $FEN)
{
	$FENarray[$i] = $FEN;
	++$i;
}

FENtomoves( ); // (chessutils.inc.php)

returnGameInfo($_SESSION['game_id']);
echo getPGN( );

