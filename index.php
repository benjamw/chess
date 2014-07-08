<?php

// get the scripty stuff
// trying to separate code from output
// for easier deciphering of both
require 'includes/index.inc.php';

// if $page is not set, there is something wrong
if ( ! isset($page))
{
	header('Location: login.php');
	exit;
}

$contents = (isset($errMsg) && ('' != $errMsg)) ? '<div class="warning">'.$errMsg.'</div>' : '';

// generate the page based on which page we are looking at
switch ($page)
{
// ====================================================
	case 'mygames' :
		$contents .= '
			<form name="games" action="chess.php" method="post">
				<h2>Continue Your Games</h2>
				';

		$label  = 'Games in Progress';
		$no_data_label = 'You currently do not have any games in progress.';

		$contents .= get_game_table($label, $no_data_label, $active, $type = 'mine');

		$contents .= '
				<div>
					<br />
					Will both players play from the same computer?
					<label><input name="rdoShare" type="radio" value="yes" />Yes</label> |
					<label><input name="rdoShare" type="radio" value="no" checked="checked" />No</label>
				</div>
				<input type="hidden" name="game_id" value="" />
			</form>
		';

		$expire = (0 != $CFG_EXPIREGAME) ? '<strong>WARNING!</strong><br />Games will be deleted after '.$CFG_EXPIREGAME.' days of inactivity.' : '';

		$hint = array(
			'Select a game from the list and resume play by clicking anywhere on the row.' ,
			'Colored entries indicate that it is your turn.' ,
			$expire ,
		);
		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'current' :
		$contents .= '
			<form name="games" action="watchgame.php" method="post">
				<h2>View All Current Games</h2>
				';

		$label  = 'Current Games';
		$no_data_label = 'There are currently no active games to view.';

		$contents .= get_game_table($label, $no_data_label, $others, $type = 'others');

		$contents .= '
				<input type="hidden" name="game_id" value="" />
				<input type="hidden" name="rdoShare" value="no" />
				<input type="hidden" name="rdoShare" value="no" /> <!-- two are needed to simulate two radio buttons -->
			</form>
		';

		$hint = 'Select a game to view from the list by clicking anywhere on the row.';
		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'finished' :
		$contents .= '
			<form name="games" action="watchgame.php" method="post">
				<h2>View All Completed Games</h2>
				';

		$label  = 'Completed Games';
		$no_data_label = 'There are currently no completed games in the database.';

		$contents .= get_game_table($label, $no_data_label, $done, $type = 'finished');

		$contents .= '
				<input type="hidden" name="game_id" value="" />
				<input type="hidden" name="rdoShare" value="no" />
				<input type="hidden" name="rdoShare" value="no" /> <!-- two are needed to simulate two radio buttons -->
			</form>
		';

		$expire = (0 != $CFG_EXPIREGAME) ? '<strong>WARNING!</strong><br />Games will be deleted after '.$CFG_EXPIREGAME.' days of inactivity.' : '';

		$hint = array(
			'Select a game to view from the list by clicking anywhere on the row.' ,
			'Or <a href="./pgn">select a game to view</a> and loading the pgn file into your favorite chess program.' ,
			'A free pgn viewer can be downloaded from: <a href="http://www.tim-mann.org/xboard.html">XBoard and WinBoard</a>' ,
			$expire ,
		);
		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'invite' :
		$contents .= '
			<form name="response" action="index.php?page=invite" method="post">
				<h2>Pending Invitations</h2>
				';

		$label  = 'Invitations from other players';
		$no_data_label = 'You are not currently invited to any games.';

		$contents .= get_game_table($label, $no_data_label, $invites, $type = 'invite');

		$contents .= '
					<input type="hidden" name="respond" value="" />
					<input type="hidden" name="message_from" value="" />
					<input type="hidden" name="game_id" value="" />
				</form>
				<hr class="fancy" />
				<form name="withdraw" action="index.php?page=invite" method="post">
					';

		$label  = 'Invitations from you';
		$no_data_label = 'You have no current unanswered invitations.';

		$contents .= get_game_table($label, $no_data_label, $outvites, $type = 'invite');

		$contents .= '
				<input type="hidden" name="game_id" value="" />
				<input type="hidden" name="withdrawl" value="yes" />
			</form>
			<br /><br />
			<form name="challenge" action="index.php?page=invite" method="post">
				<h2>Issue an Invitation</h2>
				Select Opponent:<br />
				<select name="opponent">
					';

					$query = "
						SELECT P.*
							, (
								SELECT COUNT(*)
								FROM ".T_GAME."
								WHERE (
										g_white_player_id = P.p_id
										OR g_black_player_id = P.p_id
									)
									AND g_game_message IN ('', 'Player Invited')
							) AS num_games
						FROM ".T_PLAYER." AS P
						WHERE p_id != '{$_SESSION['player_id']}'
						ORDER BY p_username
					";
					$players = $mysql->fetch_array($query, __LINE__, __FILE__);

					foreach ($players as $player)
					{
						if ($player['num_games'] >= $player['p_max_games'])
						{
							continue;
						}

						$contents .= "<option value=\"{$player['p_id']}\"> {$player['p_username']} - {$player['p_first_name']} {$player['p_last_name']} ({$player['p_rating']})</option>";
					}

			$contents .= '
				</select><br />
				<br />
				Your Color:<br />
				<label for="colorR"><input name="color" id="colorR" type="radio" value="random" checked="checked" />Random</label> |
				<label for="colorW"><input name="color" id="colorW" type="radio" value="white" />White</label> |
				<label for="colorB"><input name="color" id="colorB" type="radio" value="black" />Black</label><br />
				<br />';

			if (false != $CFG_CHESS960)
			{
				$contents .= 'Chess960 ID: &nbsp; <a href="#" class="help" onclick="window.open(\'./help/c960.html\',\'help\',\'resizable,scrollbars,width=600,height=500,top=50,left=50\',\'_blank\');return false;">?</a><br />
				<input name="txtId960" type="text" maxlength="3" /> <input type="button" name="randomid960" value="Random" onclick="txtId960.value = Math.floor(Math.random( ) * 960);" />
				<div class="instruction">
					Enter a number between 0-959 to play Chess960, or press \'Random\' to generate a number for you.<br />
					Leave this field blank (or enter 518) to play normal chess.
				</div>
				';
			}

			$contents .= '<input type="button" value="Invite" class="button" onclick="validateInvite( );" />
			</form>
		';

		$hint = array(
			'This is an overview of all your pending invitations.' ,
			'You can Accept or Decline any invitation to a new game, or you can withdraw your invitations to other players.' ,
			'You can also select an opponent and send them an invitation to play a new game.' ,
		);
		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'stats' :
		$contents .= '
			<h2>View Player / Game Statistics</h2>
		';

		$contents .= get_stats_table('Longest Games', $daysLdata, 'days_long');
		$contents .= get_stats_table('Longest Games', $movesLdata, 'moves_long');
		$contents .= get_stats_table('Shortest Games', $daysSdata, 'days_short');
		$contents .= get_stats_table('Shortest Games', $movesSdata, 'moves_short');
		$contents .= get_stats_table('Longest Streaks', $streakdata, 'win_streak');

		$table_id = get_table_id( );
		$contents .= '
				<table class="sort-table playerstats" id="'.$table_id.'">
					<caption>Player Statistics</caption>
					<thead>
						<tr>
							<th title="Player Username">Player</th>
							<th title="Player ELO Rating - Player rating is initialized at '.$CFG_RATING_START.' when the player
								first signs up and is calculated using the ELO rating algorithm using
								'.$CFG_RATING_STEP.' as the max rating change.">Rating</th>
							<th title="Total Wins">Wins</th>
							<th title="Total Draws">Draws</th>
							<th title="Total Losses">Losses</th>
						</tr>
					</thead>
					<tbody>
						';

					$i = 0;
					foreach ($playerdata as $tmpGame)
					{
						$alt = (0 == ($i % 2)) ? ' class="alt"' : '';

						$contents .= "<tr{$alt}>
							<td>{$tmpGame['p_username']}</td>
							<td>{$tmpGame['p_rating']}</td>
							<td>{$tmpGame['p_wins']}</td>
							<td>{$tmpGame['p_draws']}</td>
							<td>{$tmpGame['p_losses']}</td>
						</tr>";

						++$i;
					}

				$contents .= '
					</tbody>
				</table>
		';

		$contents .= get_sorttable_script($table_id, 'StringCI,Number,Number,Number,Number');

		$hint = array(
			'Here you can view the Top 5 games in various categories.' ,
			'You can review any game by noting the Game ID in the list, then clicking \'Watch Game\' next to that game in the <a href="./pgn/">PGN game list</a>.' ,
			'All games in these lists are completed games.' ,
			'Player ratings are started at '.$CFG_RATING_START.' and have a maximum value change of '.$CFG_RATING_STEP.'.' ,
		);
		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'messages' :

		$contents = '
				<script type="text/javascript" src="javascript/messages.js"></script>

				<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div>
					<input type="button" name="send" id="send" value="Send Message" />
				</div></form>';


		// INBOX
		$messages = $Message->get_inbox_list( );
		$table_format = array(
			array('SPECIAL_CLASS', 'my_empty(\'[[[view_date]]]\')', 'highlight') ,
			array('SPECIAL_HTML', 'true', 'id="msg[[[message_id]]]"') ,

			array('Id', 'message_id') ,
			array('Subject', '###@htmlentities(strmaxlen(html_entity_decode(\'[[[subject]]]\', ENT_QUOTES), 25), ENT_QUOTES, \'ISO-8859-1\', false)') ,
			array('From', '###\'[[[sender]]]\'.(([[[global]]]) ? \' <span class="highlight">(<abbr title="GLOBAL">G</abbr>)</span>\' : \'\')') ,
			array('Date Sent', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[send_date]]]\'), strtotime(\'[[[create_date]]]\'))') ,
			array('Date Read', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[view_date]]]\'), \'Never\')') ,
			array('Date Expires', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[expire_date]]]\'), \'Never\')') ,
			array('<input type="checkbox" id="in_all" />', '<input type="checkbox" name="ids[]" value="[[[message_id]]]" class="in_box" />', 'false', 'class="edit"') ,
		);
		$table_meta = array(
			'sortable' => true ,
			'no_data' => '<p>There are no messages in your inbox.</p><!-- NO_INBOX -->' ,
			'caption' => 'Inbox' ,
		);
		$table = get_table($table_format, $messages, $table_meta);

		// add the message edit form if we have messages shown
		if (false === strpos($table, 'NO_INBOX')) {
			$contents .= '
				<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="action">
					'.$table.'
					<select name="action" id="in_action" style="float:right;">
						<option value="">With Selected:</option>
						<option value="read">Mark as Read</option>
						<option value="unread">Mark as Unread</option>
						<option value="delete">Delete</option>
					</select>
				</div></form>';
		}
		else {
			$contents .= $table;
		}


		// OUTBOX
		$result = $Message->get_outbox_list( );
		$table_format = array(
			array('SPECIAL_CLASS', ' ! [[[sent]]]', 'unsent') ,
			array('SPECIAL_HTML', 'true', 'id="msg[[[message_id]]]"') ,

			array('Id', 'message_id') ,
			array('Subject', '###@htmlentities(strmaxlen(html_entity_decode(\'[[[subject]]]\'), 25), ENT_QUOTES, \'ISO-8859-1\', false)') ,
			array('To', 'recipients') ,
			array('Date Sent', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[send_date]]]\'), strtotime(\'[[[create_date]]]\'))') ,
			array('Date Expires', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[expire_date]]]\'), \'Never\')') ,
			array('<input type="checkbox" id="out_all" />', '<input type="checkbox" name="ids[]" value="[[[message_id]]]" class="out_box" />', 'false', 'class="edit"') ,
		);
		$table_meta = array(
			'sortable' => true ,
			'no_data' => '<p>There are no messages in your outbox.</p><!-- NO_OUTBOX -->' ,
			'caption' => 'Outbox' ,
		);
		$table = get_table($table_format, $result, $table_meta);

		// add the message edit form if we have messages shown
		if (false === strpos($table, 'NO_OUTBOX')) {
			$contents .= '
				<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="action">
					'.$table.'
					<select name="action" id="out_action" style="float:right;">
						<option value="">With Selected:</option>
						<option value="delete">Delete</option>
					</select>
				</div></form>';
		}
		else {
			$contents .= $table;
		}


		// ADMIN LIST
		if (false && $GLOBALS['Player']->is_admin) {
			$result = $Message->get_admin_list( );
			$table_format = array(
				array('SPECIAL_CLASS', ' ! [[[sent]]]', 'unsent') ,
				array('SPECIAL_HTML', 'true', 'id="msg[[[message_id]]]"') ,

				array('Id', 'message_id') ,
				array('Subject', '###@htmlentities(strmaxlen(html_entity_decode(\'[[[subject]]]\'), 25), ENT_QUOTES, \'ISO-8859-1\', false)') ,
				array('From', 'sender') ,
				array('To', 'recipients') ,
				array('Date Sent', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[send_date]]]\'), strtotime(\'[[[create_date]]]\'))') ,
				array('Date Expires', '###@ifdateor(\''.$CFG_LONGDATE.'\', strtotime(\'[[[expire_date]]]\'), \'Never\')') ,
			);
			$table_meta = array(
				'sortable' => true ,
				'no_data' => '<p>There are no messages in the admin list.</p><!-- NO_ADMIN -->' ,
				'caption' => 'Admin List' ,
			);
			$table = get_table($table_format, $result, $table_meta);

			// no form
			$contents .= $table;
		}


		$hints = array(
			'Click anywhere on a row to read your messages.' ,
			'<span class="highlight">Colored inbox entries</span> indicate messages that have not been read.' ,
			'<span class="highlight">(<abbr title="GLOBAL">G</abbr>)</span> indicates a GLOBAL message sent by an administrator.',
			'<span class="highlight">Colored outbox entries</span> indicate messages that have not been sent.' ,
			'Colored outbox <span class="highlight">recipient</span> entries indicate messages that have not been read.' ,
			'Colored outbox <span class="highlight">sent dates</span> indicate messages that have not been sent.' ,
		);

		$html = get_item($contents, $hints);

		break;

// ====================================================
	case 'send' :

		$contents = '
			<style type="text/css">@import url(css/vader/jquery-ui-1.8.13.custom.css);</style>
			<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
			<script type="text/javascript" src="javascript/messages.js"></script>
		';

		$subject = htmlentities($message['subject'], ENT_QUOTES, 'ISO-8859-1', false);
		$message_text = htmlentities($message['message'], ENT_QUOTES, 'ISO-8859-1', false);

		$contents .= <<< EOT

			<div id="content" class="msg">
				<div class="link_date">
					<a href="index.php?page=messages">Return to Inbox</a>
					<?php echo date(\''.$CFG_LONGDATE.'\'); ?>
				</div>
				<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
					<div>
						<div class="info">Press and hold CTRL while selecting to select multiple recipients</div>
						<label for="user_ids">Recipients</label><select name="user_ids[]" id="user_ids" multiple="multiple" size="5">
						{$recipient_options}
						</select>
					</div>
					<div><label for="send_date">Send Date</label><input type="text" name="send_date" id="send_date" /> <span class="info">Leave blank to send now</span></div>
					<div><label for="expire_date">Expiration Date</label><input type="text" name="expire_date" id="expire_date" /> <span class="info">Leave blank to never expire</span></div>
					<div><label for="subject">Subject</label><input type="text" name="subject" id="subject" value="{$subject}" size="50" maxlength="255" /></div>
					<div><label for="message">Message</label><textarea name="message" id="message" rows="15" cols="50">{$message_text}</textarea></div>
					<div><label>&nbsp;</label><input type="submit" name="submit" value="Send Message" /></div>
				</div></form>
			</div>

EOT;

		$html = get_item($contents, '');

		break;

// ====================================================
	case 'read' :

		$contents = '
			<script type="text/javascript" src="scripts/messages.js"></script>';


			ob_start( );

?>

	<div id="content" class="msg">
		<div class="link_date">
			<a href="index.php?page=messages">Return to Inbox</a>
			Sent: <?php echo @ifdateor($CFG_LONGDATE, strtotime($message['send_date']), strtotime($message['create_date'])); ?>
		</div>
		<h2 class="subject"><?php echo $message['subject']; ?> <span class="sender">From: <?php echo $message['recipients'][0]['sender']; ?></span></h2>
		<div class="sidebar">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"><div class="buttons">
				<div class="prevnext">
					<?php if ($prev) { echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?id='.$prev.'">&laquo; Newer</a>'; } ?>
					<?php if ($prev && $next) { echo ' <span>|</span> '; } ?>
					<?php if ($next) { echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?id='.$next.'">Older &raquo;</a>'; } ?>
				</div>

<?php if ($message['allowed']) { ?>

				<input type="hidden" name="message_id" id="message_id" value="<?php echo $message['message_id']; ?>" />
				<input type="hidden" name="type" id="type" />
				<input type="button" id="delete" value="Delete" />

	<?php if ($message['recipients'][0]['from_id'] != $_SESSION['player_id']) { ?>

				<input type="button" id="reply" value="Reply" />
				<input type="button" id="forward" value="Forward" />

	<?php } else { ?>

				<input type="button" id="resend" value="Resend" />

	<?php } ?>

<?php } ?>

			</div></form>
			<div id="recipient_list">
				<h4>Recipient List</h4>

				<?php if ($message['global']) { echo '<span class="highlight">GLOBAL</span>'; } ?>

<?php if ( ! $message['global'] || $_SESSION['is_admin']) { ?>

				<ul>

<?php

		foreach ($message['recipients'] as $recipient) {
			if ($recipient['from_id'] == $recipient['to_id']) {
				continue;
			}

			$classes = array( );
			if (is_null($recipient['view_date'])) {
				$classes[] = 'unread';
			}

			if ($recipient['deleted']) {
				$classes[] = 'deleted';
			}

			$class = '';
			if (count($classes)) {
				$class = ' class="'.implode(' ', $classes).'"';
			}

			echo "<li{$class}>{$recipient['recipient']}</li>\n";
		}

?>

				</ul>

<?php } ?>

			</div>
		</div>

		<p class="message"><?php echo $message['message']; ?></p>

	</div>

	<?php

		$contents .= ob_get_contents( );

		ob_end_clean( );

		$html = get_item($contents, '');

		break;

// ====================================================
	case 'prefs' :
		$contents .= '
			<form name="preferences" method="post" action="index.php?page=prefs">
				<h2>Preferences</h2>
				<fieldset>
					<legend>History Notation</legend>
					<select name="selHistory">
						<option value="coord"'.get_selected($_SESSION['pref_history'], 'coord', true).'>Coordinate</option>
						<option value="alg"'.get_selected($_SESSION['pref_history'], 'alg', true).'>Algebraic</option>
						<option value="longalg"'.get_selected($_SESSION['pref_history'], 'longalg', true).'>Long Algebraic</option>
						<!-- <option value="desc"'.get_selected($_SESSION['pref_history'], 'desc', true).'>Descriptive</option> -->
						<option value="int"'.get_selected($_SESSION['pref_history'], 'int', true).'>International</option>
						<option value="verbous"'.get_selected($_SESSION['pref_history'], 'verbous', true).'>Verbose</option>
					</select>
					<a href="#" class="help" onclick="window.open(\'./help/notation.html\',\'help\',\'resizable,scrollbars,width=600,height=500,top=50,left=50\',\'_blank\');return false;">?</a>
				</fieldset><br />
				<fieldset>
					<legend>Theme</legend>
					';

					// open up the images directory and collect the folder names
					$dir = opendir('images');

					while (false !== ($file = readdir($dir)))
					{
						if (is_dir('images/'.$file) && (false === strpos($file, '.'))) // scanning for visible subfolders only
						{
							$dirlist[] = $file;
						}
					}

					closedir($dir);

					$label = 'A';
					foreach ($dirlist as $theme)
					{
						$sel = ($_SESSION['pref_theme'] == $theme) ? ' checked="checked"' : '';
						$contents .= "\t\t\t\t\t\t<label for=\"rdoTheme{$label}\"><input name=\"rdoTheme\" id=\"rdoTheme{$label}\" type=\"radio\" value=\"{$theme}\"{$sel} />{$theme}</label><br />\n";
						++$label; // increment label counter
					}

			$contents .= '
				</fieldset><br />
				';

				$xltm = '';
				if (false != $_SESSION['pref_show_last_move'])
				{
					$xltm = ' checked="checked"';
				}

			$contents .= '
				<label for="boxLastMove"><input type="checkbox" name="boxLastMove" id="boxLastMove"'.$xltm.' />Show Previous Move Indicator</label>
				<div class="instruction">Un-checking this box will remove the circles displayed on the chessboard.</div>
				<br />
				<input type="text" name="txtmaxGames" value="'.$_SESSION['pref_max_games'].'" /> Max Concurrent Games<br />
				<input type="text" name="txtReload" value="'.$_SESSION['pref_auto_reload'].'" /> Auto Reload';

				if (0 != $CFG_MINAUTORELOAD)
				{
					$contents .= ' (min: '.$CFG_MINAUTORELOAD.' secs)';
				}

			$contents .= '<br />
				<br />
				<input type="submit" class="button" value="Update" />
				<input type="hidden" name="todo" value="UpdatePrefs" />
			</form>
		';

		$hint = array(
			'You can customize WebChess with these general settings.' ,
			'<a href="themes.php">View the different themes</a>' ,
		);

		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'personal' :
		$contents .= '
			<form name="personal" action="index.php?page=personal" method="post">
				<h2>Personal information</h2>
				<input name="txtFirstName" type="text" class="inputbox" value="'.$_SESSION['first_name'].'" /> First Name<br />
				<input name="txtLastName" type="text" class="inputbox" value="'.$_SESSION['last_name'].'" /> Last Name<br />
				';

			if ($CFG_USEEMAIL)
			{
				$contents .= '<input type="text" name="txtEmail" value="'.$_SESSION['email'].'" /> Email Address <br />
				';
			}

			if ($CFG_CHANGEUSERNAME)
			{
				$contents .= '<input name="txtUsername" type="text" class="inputbox" value="'.$_SESSION['username'].'" /> Username<br />
				';
			}

			$contents .= '<input name="pwdOldPassword" type="password" class="inputbox" /> Current Password<br />
				<input name="pwdPassword" type="password" class="inputbox" /> New Password<br />
				<input name="pwdPassword2" type="password" class="inputbox" /> New Password Again<br />
				<br />
				<input type="button" value="Update" class="button" onclick="validatepersonal( )" />
			</form>
		';

		$hint = array(
			'Here you can change your personal information. Remember to press the \'Update\' button to store the changes.' ,
			'Leave all password fields blank to keep your old password.' ,
		);

		if ($CFG_USEEMAIL)
		{
			$hint[] = 'Enter a valid email address if you would like to be notified when your opponent makes a move. Leave blank otherwise.';
		}

		$html = get_item($contents, $hint);

		break;

// ====================================================
	case 'admin' :
		if (true != $_SESSION['is_admin'])
		{
			header('Location: index.php');
			exit;
		}

		$table_id = get_table_id( );
		$contents .= '
			<form name="Admin" action="index.php?page=admin" method="post">
				<h2>Administration</h2>
				Click the relevant box next to each user, then click on \'Admin\' below to affect those changes.
				<table class="sort-table" id="'.$table_id.'">
					<thead>
						<tr>
							<th>ID</th>
							<th>Username</th>
							<th>First</th>
							<th>Last</th>
							<th>Email</th>
							<th>Create Date</th>
							<th>Reset Pass ?</th>
							<th>Admin ?</th>
							<th>Delete ?</th>
						</tr>
					</thead>
					<tbody>
						';
					$i = 0;
					foreach ($admin as $row)
					{
						$alt = ( $i % 2 == 0 ) ? ' class="alt"' : '';
						$check = (0 != $row['p_is_admin']) ? ' checked="checked"' : '';

						$contents .= '<tr'.$alt.'>
							<td class="numeric">'.$row['p_id'].'</td>
							<td class="username">'.$row['p_username'].'</td>
							<td class="firstname">'.$row['p_first_name'].'</td>
							<td class="lastname">'.$row['p_last_name'].'</td>
							<td>'.$row['p_email'].'</td>
							<td>'.date($CFG_SHORTDATE, $row['u_created']).'</td>
							<td class="passbox"><input type="checkbox" name="resetpass[]" value="'.$row['p_id'].'" /></td>
							<td class="adminbox"><input type="checkbox" name="admin[]" value="'.$row['p_id'].'"'.$check.' /></td>
							<td class="checkbox"><input type="checkbox" name="delete[]" value="'.$row['p_id'].'" /></td>
						</tr>';

						++$i;
					}
				$contents .= '
					</tbody>
				</table>
				';

			$contents .= get_sorttable_script($table_id, 'Number,StringCI,StringCI,StringCI,StringCI,DateTime,None,None,None');

			$contents .= '
				<input type="submit" value="Continue" name="submit" class="button" /><br />
				<br />
				<div class="inputlabel"><input type="button" value="Email ALL registered users" onclick="javascript:window.open(\'massmail.php\',\'message\',\'resizable,width=600,height=500,top=100,left=100\',\'_blank\');return false;" /></div>
			</form>
		';

		$hint = array(
			'Here you can set users to be admin, or delete users.' ,
			'When deleting users, the script will also delete all information regarding that user, such as games, messages, etc.' ,
			'You can also send an e-mail to all registered users by clicking the link below the form.'
		);

		$html = get_item($contents, $hint);

		break;

// ====================================================
	default :
		// do nothing, it's already been done in index.inc.php
		break;
}


// this needs to run after the sections above, because it may alter the variables
// set default values for the menu vars, and if needed, highlight them
$menu_data['numMyturn']   = (isset($numMyturn) && 0 < $numMyturn) ? "<span class=\"notice\">{$numMyturn}</span>" : 0;
$menu_data['numActive']   = (isset($numActive)) ? $numActive : 0;
$menu_data['numOthers']   = (isset($numOthers)) ? $numOthers : 0;
$menu_data['numDone']     = (isset($numDone)) ? $numDone : 0;
$menu_data['numFiles']    = (isset($numFiles)) ? $numFiles : 0;
$menu_data['numInvites']  = (isset($numInvites) && 0 < $numInvites) ? "<span class=\"notice\">{$numInvites}</span>" : 0;
$menu_data['numOutvites'] = (isset($numOutvites)) ? $numOutvites : 0;
$menu_data['numMsgs']     = (isset($numMsgs) && 0 < $numMsgs) ? $numMsgs : 0;
$menu_data['newMsgs']     = (isset($newMsgs) && 0 < $newMsgs) ? "<span class=\"notice\">{$newMsgs}</span>" : 0;

$foot_data['numPlayers'] = $numPlayers;
$foot_data['numGames']   = $numGames;
$foot_data['totGames']   = $totGames;

// now that we're all done building the sections, output the page
$head_extra = '
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="javascript/sortabletable.js"></script>
	<script type="text/javascript" src="javascript/messages.js"></script>
	<script type="text/javascript" src="javascript/index.js.php"></script>
';

echo get_header($menu_data, 'Main Menu', $head_extra);
echo $html;
echo get_footer($foot_data);

call(time( ));call($GLOBALS);
