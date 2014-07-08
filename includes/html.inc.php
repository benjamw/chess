<?php

// this page contains various html functions to save space elsewhere
function get_header($menu_data, $title = '', $head_extra = '')
{
	global $CFG_NAVLINKS, $CFG_COLOR_CSS;

	$title .= ('' != $title) ? ' :: '.$title : '';

	$html = <<< EOF
<!doctype html>
<html lang="en">
<head>

	<title>{$title}</title>

	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Content-Style-Type" content="text/css" />

	<link rel="stylesheet" type="text/css" media="screen" href="css/messages.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/layout.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/{$CFG_COLOR_CSS}" />

	{$head_extra}

</head>

<body>
	<div id="links">{$CFG_NAVLINKS}</div>
	<h1><img src="images/WebChess.png" alt="WebChess" /></h1>
	<div id="wrapper">
EOF;

	if (is_array($menu_data))
	{
		$html .= '
		<div id="menu">
			<ul>
				<li'.get_active('mygames').'><a href="?page=mygames">Your Games ('.$menu_data['numMyturn'].'|'.$menu_data['numActive'].')</a></li>
				<li'.get_active('current').'><a href="?page=current">Current Games ('.$menu_data['numOthers'].')</a></li>
				<li'.get_active('finished').'><a href="?page=finished">Finished Games ('.$menu_data['numDone'].'|'.$menu_data['numFiles'].')</a></li>
				<li'.get_active('invite').'><a href="?page=invite">Invitations ('.$menu_data['numInvites'].'|'.$menu_data['numOutvites'].')</a></li>
				<li'.get_active('stats').'><a href="?page=stats">Statistics</a></li>
				<li'.get_active('messages').'><a href="?page=messages">Messages ('.$menu_data['numMsgs'].'|'.$menu_data['newMsgs'].')</a></li>
				<li'.get_active('prefs').'><a href="?page=prefs">Preferences</a></li>
				<li'.get_active('personal').'><a href="?page=personal">Personal</a></li>
				';

				if ( true == $_SESSION['is_admin'] )
				{
					$html .= '<li'.get_active('admin').'><a href="?page=admin">Admin</a></li>';
				}

		$html .= '
				<li><a href="?logout">Logout</a></li>
			</ul>
		</div>
		';
	}

	return $html;
}


function get_footer($foot_data = null)
{
	$html = '
		<div id="footerspacer">&nbsp;</div>
		<div id="footer">';

		if (null != $foot_data)
		{
			$html .= "
			<span>Total Players - {$foot_data['numPlayers']}</span>
			<span>Active Games - {$foot_data['numGames']}</span>
			<span>Games Played - {$foot_data['totGames']}</span>";
		}

	$html .= '
		</div>
	</div>
</body>
</html>';

	return $html;
}



function get_item($contents, $hint)
{
	global $CFG_LONGDATE;

	$hint_html = "\n\t\t\t<p><strong>Welcome, {$_SESSION['username']}</strong></p>";

	if (is_array($hint))
	{
		foreach ($hint as $line)
		{
			$hint_html .= "\n\t\t\t<p>{$line}</p>";
		}
	}
	else
	{
		$hint_html .= "\n\t\t\t<p>{$hint}</p>";
	}

	$html = '
		<div id="notes">
			<div id="date">'.date($CFG_LONGDATE).'</div>
			'.$hint_html.'
		</div>
		<div id="content">
			'.$contents.'
		</div>
	';

	return $html;
}


//*
function get_game_table($label, $no_data_label, $data, $type = 'full')
{
	global $mysql;
	global $CFG_CHESS960, $CFG_LONGDATE;

	if ( ! is_array($data) || (0 == count($data)))
	{
		$html = '<div class="notable">'.$no_data_label.'</div>';
	}
	else
	{
		$sort_c960     = ($CFG_CHESS960)       ? ' , StringCI' : '';
		$sort_finished = ('finished' == $type) ? ' , StringCI' : '';
		$sort_invite   = ('invite' == $type)   ? ' , None' : ' , LongDate';
		$sort_moves    = ('invite' == $type)   ? ' , StringCI' : ' , Number';
		$sort_gameid   = ('invite' == $type)   ? '' : 'Number , ';

		$sort = $sort_gameid . 'StringCI , StringCI , StringCI ' . $sort_moves . $sort_c960 . $sort_finished . ' , LongDate ' . $sort_invite;
		call($sort);

		$table_id = get_table_id( );

		$link_table = ('invite' != $type) ? ' link-table' : '';

		$html = '<table class="sort-table'.$link_table.'" id="'.$table_id.'">
					<caption>'.$label.'</caption>
					<thead>
						<tr>
							';

						if ('invite' != $type)
						{
							$html .= '<th title="Game ID Number">Game</th>';
						}

						$html .= '
							<th title="White\'s username">White</th>
							<th title="Black\'s username">Black</th>
							<th title="Who\'s turn it is">Turn</th>
							';

						if ('invite' != $type)
						{
							$html .='<th title="Moves">Moves</th>';
						}
						else
						{
							$html .= '<th title="Status">Status</th>';
						}

						if ($CFG_CHESS960)
						{
							$html .= '<th title="Game Type">Type</th>';
						}

						if ('finished' == $type)
						{
							$html .= '<th title="Game Result">Result</th>';
						}

						$html .= '
							<th title="Start Date of the Game">Start Date</th>
							';

						if ('invite' != $type)
						{
							$html .='<th title="Date / Time of Last Move">Last Move</th>';
						}
						else
						{
							$html .= '<th title="Action">Action</th>';
						}

						$html .= '
						</tr>
					</thead>
					<tbody>
						';

					$i = 0;
					foreach ($data as $game)
					{
						// we need some default values for some variables
						$myturn = false;
						$white = '';
						$black = '';
						$curMove = (0 == ($game['num_moves'] % 2)) ? 'white' : 'black';

						switch ($type)
						{
							case 'mine' :
								$link = ' onclick="loadGame('.$game['g_id'].');"';

								// based on number of moves, figure out who's turn it is
								$color   = ($game['g_white_player_id'] == $_SESSION['player_id']) ? 'white' : 'black';

								if ($curMove == $color)
								{
									$myturn = true;
								}

								break;

							case 'others' :
								$link = ' onclick="watchGame('.$game['g_id'].');"';
								break;

							case 'finished' :
								$link = ' onclick="watchGame('.$game['g_id'].');"';

								if ($game['g_white_player_id'] == $_SESSION['player_id'])
								{
									$link = ' onclick="loadGame('.$game['g_id'].');"';
									$white = ' class="notice"';
								}
								elseif ($game['g_black_player_id'] == $_SESSION['player_id'])
								{
									$link = ' onclick="loadGame('.$game['g_id'].');"';
									$black = ' class="notice"';
								}

								if (is_null($game['g_game_message']))
								{
									$result = '';
								}
								else
								{
									if ('Draw' == $game['g_game_message'])
									{
										$result = '½-½';
									}
									elseif ('Player Resigned' == $game['g_game_message'])
									{
										$result  = ('white' == $game['g_message_from']) ? '0-1' : '1-0';
										$result .= ' <abbr title="Resignation">R</abbr>';
									}
									elseif ('Checkmate' == $game['g_game_message'])
									{
										$result  = ('white' == $game['g_message_from']) ? '1-0' : '0-1';
										$result .= ' <abbr title="Checkmate">CM</abbr>';
									}
									else
									{
										$result = '';
									}
								}

								break;

							case 'invite' :
								$link = '';

								$from = ($game['white_username'] == $_SESSION['username']) ? 'white' : 'black';

								$status = substr($game['g_game_message'], strrpos(trim($game['g_game_message']), ' ') + 1);
								$status = ('Declined' == $status) ? '<span class="notice">'.$status.'</span>' : $status;

								// based on which it is, outvite or invite, make the buttons
								if (isset($game['invite']))
								{
									$buttons = '
										<input class="tblbutton" type="button" value="Accept" onclick="sendresponse(\'accepted\',\''.$from.'\','.$game['g_id'].')" />
										<input class="tblbutton" type="button" value="Decline" onclick="sendresponse(\'declined\',\''.$from.'\','.$game['g_id'].')" />
									';
								}
								else
								{
									$buttons = '
										<input class="tblbutton" type="button" value="Withdraw" onclick="withdrawrequest('.$game['g_id'].')" />
									';
								}

								break;
						} // end type switch

						$alt = (0 == ($i % 2)) ? true : false;
						call($myturn);
						$class = '';
						if ($myturn || $alt)
						{
							$class = ' class="';

							if ($myturn && $alt) {
								$class .= 'myturn alt';
							}
							elseif ($alt) {
								$class .= 'alt';
							}
							else {
								$class .= 'myturn';
							}

							$class .= '"';
						}

						$html .= "<tr{$link}{$class}>\n";

						if ('invite' != $type)
						{
							$html .= "\t<td class=\"numeric\">{$game['g_id']}</td>\n";
						}

						$html .= "\t<td{$white}>{$game['white_username']}</td>\n";
						$html .= "\t<td{$black}>{$game['black_username']}</td>\n";
						$html .= "\t<td>".ucfirst($curMove)."</td>\n";

						if ('invite' != $type)
						{
							$html .= "\t<td class=\"numeric\">".floor($game['num_moves'] / 2)."</td>\n";
						}
						else
						{
							$html .= "\t<td>".$status."</td>\n";
						}

						if ($CFG_CHESS960)
						{
							$gametype = (518 != $game['g_id960']) ? 'C960' : 'Normal';
							$html .= "\t<td>{$gametype}</td>\n";
						}

						if ('finished' == $type)
						{
							$html .= "\t<td>{$result}</td>\n";
						}

						$html .= "\t<td class=\"date\">".date($CFG_LONGDATE, $game['u_date_created'])."</td>\n";

						if ('invite' != $type)
						{
							$html .= "\t<td class=\"date\">".date($CFG_LONGDATE, $game['u_last_move'])."</td>\n";
						}
						else
						{
							$html .= "\t<td class=\"buttons\">".$buttons."</td>\n";
						}

						$html .= '</tr>';

						++$i;
					}

				$html .= '
					</tbody>
				</table>
				';

		$html .= get_sorttable_script($table_id, $sort);
	}

	return $html;
}
//*/


function get_stats_table($label, $data, $type)
{
	switch ($type)
	{
		case 'days_long' :
			$item_id = 'Game';
			$time_id = 'Days';
			$title = 'Number of days the game lasted';
			$id_field = 's_id';
			$count_field = 's_days';
			break;

		case 'days_short' :
			$item_id = 'Game';
			$time_id = 'Days';
			$title = 'Number of days the game lasted';
			$id_field = 's_id';
			$count_field = 's_days';
			break;

		case 'moves_long' :
			$item_id = 'Game';
			$time_id = 'Ply';
			$title = 'Number of half moves (ply) for the game to end';
			$id_field = 's_id';
			$count_field = 's_moves';
			break;

		case 'moves_short' :
			$item_id = 'Game';
			$time_id = 'Ply';
			$title = 'Number of half moves (ply) for the game to end';
			$id_field = 's_id';
			$count_field = 's_moves';
			break;

		case 'win_streak' :
			$item_id = 'Player';
			$time_id = 'Wins';
			$title = 'Draws neither add to, nor stop a current winning streak';
			$id_field = 'p_username';
			$count_field = 's_streak';
			break;

	}

	$html = '
			<table class="sort-table stats">
				<caption>'.$label.'</caption>
				<thead>
					<tr>
						<th>'.$item_id.'</th>
						<th title="'.$title.'">'.$time_id.'</th>
					</tr>
				</thead>
				<tbody>
					';

				$i = 0;
				foreach ($data as $item)
				{
					$alt = (0 == ($i % 2)) ? ' class="alt"' : '';

					$html .= "<tr{$alt}>
						<td>".$item[$id_field]."</td>
						<td class=\"numeric\">{$item[$count_field]}</td>
					</tr>";

					++$i;
				}

			$html .= '
				</tbody>
			</table>';

	return $html;
}


// sort_types can be a comma seperated list or an array of sort types (see sortabletable.js for available types)
function get_sorttable_script($table_id, $sort_types, $alt_class = 'alt')
{
	$html = '
		<script type="text/javascript">//<![CDATA[
			var s'.$table_id.' = new SortableTable(document.getElementById("'.$table_id.'"),
				[';

	if ( ! is_array($sort_types))
	{
		$sort_types = explode(',', $sort_types);
	}

	$types = array_map('trim', $sort_types);

	$types = '\''.implode('\',\'', $types).'\'';

	$html .= $types.']);

			// restore the class names
			s'.$table_id.'.onsort = function( )
			{
				var rows = s'.$table_id.'.tBody.rows;
				var l = rows.length;
				for (var i = 0; i < l; ++i) {
					removeClassName(rows[i], \''.$alt_class.'\');
					addClassName(rows[i], i % 2 ? \'\' : \''.$alt_class.'\');
				}
			}
		//]]></script>
	';

	return $html;
}

function get_table_id($length = 7)
{
	return 't' . substr(md5(substr(md5(uniqid(rand( ), true)), rand(0, 25), 7)), rand(0, (32 - $length)), $length);
}


function get_active($value)
{
	// make the list element active if the page matches, or if there is no page given and its the 'mygames' element
	if (( ! isset($_GET['page']) && ('mygames' == $value)) || (isset($_GET['page']) && ($value == $_GET['page'])))
	{
		return ' class="active"';
	}

	return '';
}


function get_selected($var, $val, $selected = true)
{
	if (($var === (int) $val) || ($var === (string) $val))
	{
		return (($selected) ? ' selected="selected" ' : ' checked="checked" ');
	}
	else
	{
		return ' ';
	}
}


function get_num_mine($query_results)
{
	$return_num = 0;

	foreach($query_results as $game)
	{
		// based on number of moves, figure out who's turn it is
		$curMove = (0 == ($game['num_moves'] % 2)) ? 'white' : 'black';
		$color   = ($game['g_white_player_id'] == $_SESSION['player_id']) ? 'white' : 'black';

		if ($curMove == $color)
		{
			++$return_num;
		}
	}

	return $return_num;
}


?>