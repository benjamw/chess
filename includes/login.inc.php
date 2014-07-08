<?php

// if we are logging out
if (isset($_GET['logout']))
{
	session_unset( ); // delete the session
	setcookie('WebChessData','DELETED!',time( ) - 3600); // delete the cookie
	header('Location: login.php'); // redirect to the login page
	exit;
}
call($_POST);

// if we are trying to register a new name
if (isset($_POST['register']))
{
	// test the token
	if ( ! isset($_SESSION['token']) || ($_SESSION['token'] != $_POST['token']))
	{
		call($GLOBALS);
		die('Hacking attempt detected.<br /><br />If you have reached this page in error, please go back to the login page,<br />clear your cache, refresh the page, and try to log in again.');
	}

	// set the 'log in attempted' flag
	$new_user = true;

	// check for existing user with same username
	$query = "
		SELECT p_id
		FROM ".T_PLAYER."
		WHERE p_username = '".sani($_POST['txtUsername'])."'
	";
	$mysql->query($query, __LINE__, __FILE__);

	if (0 < $mysql->num_rows( ))
	{
		echo '<script type="text/javascript">alert(\'That username is already in use. Please try again\'); window.location.replace(\'newuser.php\');</script>';
		exit( );
	}

	call(password_make($_POST['pwdPassword']));

	$query = "
		INSERT
		INTO ".T_PLAYER."
		SET p_password = '".password_make($_POST['pwdPassword'])."'
			, p_username = '".sani($_POST['txtUsername'])."'
			, p_first_name = '".sani($_POST['txtFirstName'])."'
			, p_last_name = '".sani($_POST['txtLastName'])."'
	";

	if ($CFG_USEEMAIL)
	{
		$query .= "
			, p_email = '".sani($_POST['txtEmail'])."'
		";
	}

	$query .= "
			, p_created = NOW( )
	";
	$mysql->query($query, __LINE__, __FILE__);

	// set the session var so we get logged in below
	$_SESSION['player_id'] = $mysql->fetch_insert_id( );

	// add a welcome message for the new user
	$query = "
		INSERT
		INTO ".T_TALK."
		SET t_from_player_id = 1
			, t_to_player_id = '{$_SESSION['player_id']}'
			, t_subject = 'Welcome to WebChess 2.0'
			, t_text = 'Welcome to WebChess 2.0\n\nIf you find any bugs, PLEASE send an email to me at benjam@iohelix.net as soon as possible detailing what happened and what went wrong so I can fix it.\n\nNow, please take a moment to familiarize yourself with the menu, and adjust your preferences to your liking.\nWhen that is all done, invite a fellow player to play a game, because well, that\'s what we\'re all here for!\n\nAgain, Welcome\n  --WebChess Administration'
			, t_post_date = NOW( )
	";
	$mysql->query($query, __LINE__, __FILE__);
}


// if we are already logged in, and there is nobody attempting to log in...
if (isset($_SESSION['player_id']) && ! isset($_POST['login']) && ('WebChess2-'.$CFG_SITENAME.'-'.$CFG_MAINPAGE == $_SESSION['GAME']))
{
	call('REFRESH LOGIN');
	// just refresh the session data with the (possibly new) database data
	$query = "
		SELECT *
		FROM ".T_PLAYER."
		WHERE p_id = '{$_SESSION['player_id']}'
	";
	$player = $mysql->fetch_assoc($query, __LINE__, __FILE__);
	$refreshPlayer = true;
}
// or if we have a cookie, log in using the cookie data
elseif (isset($_COOKIE['WebChessData']) && ('DELETED!' != $_COOKIE['WebChessData']) && ! isset($_POST['login']))
{
	call('COOKIE LOGIN');
	$data  = base64_decode($_COOKIE['WebChessData']);
	$ident = substr($data,0,32);
	$token = substr($data,32);
	$query = "
		SELECT *
		FROM ".T_PLAYER."
		WHERE p_ident = '{$ident}'
			AND p_token = '{$token}'
	";
	call($data);call($ident);call($token);call($query);

	if ($player = $mysql->fetch_assoc($query, __LINE__, __FILE__))
	{
		call('COOKIE OK !');
		$refreshPlayer = true;

		// regenerate the security info
		session_regenerate_id(true);
		$ident = md5(uniqid(mt_rand( ), true));
		$token = md5(uniqid(mt_rand( ), true));
		$data  = base64_encode($ident . $token);
		setcookie('WebChessData', $data, time( ) + (60 * 60 * 24 * 7));

		// save the new ident and token to the database
		$query = "
			UPDATE ".T_PLAYER."
			SET p_ident = '{$ident}'
				, p_token = '{$token}'
			WHERE p_id = '{$player['p_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);
	}
	else // cookie data is invalid
	{
		call('COOKIE INVALID !');
		session_unset( ); // delete any session vars
		setcookie('WebChessData','DELETED!',time( ) - 3600); // delete the cookie
		header('Location: login.php'); // redirect to the login page
		exit;
	}
}
// if somebody is trying to log in
elseif (isset($_POST['token']))
{
	call('REGULAR LOGIN');

	// test the token
	if (( ! isset($_SESSION['token'])) || ($_SESSION['token'] != $_POST['token']))
	{
		call($GLOBALS);
		die('Hacking attempt detected.<br /><br />If you have reached this page in error, please go back to the login page,<br />clear your cache, refresh the page, and try to log in again.');
	}

	// check for a player with supplied username and password
	$query = "
		SELECT *
		FROM ".T_PLAYER."
		WHERE p_username = '".sani($_POST['txtUsername'])."'
	";
	$player = $mysql->fetch_assoc($query, __LINE__, __FILE__);
}
else // we need to log in
{
	call('NO LOGIN DETECTED');
	call($GLOBALS);
	header('Location: login.php');
	exit;
}

// just refresh, OR log us in if such a player exists and password is good... otherwise die
if (isset($refreshPlayer) || ((false !== $player) && password_test($_POST['pwdPassword'], $player['p_password'])))
{
	$_SESSION['GAME'] = 'WebChess2-'.$CFG_SITENAME.'-'.$CFG_MAINPAGE; // prevent cross script session stealing due to refresh login
	$_SESSION['player_id'] = $player['p_id'];
	$_SESSION['last_input_time'] = time( );
	$_SESSION['first_name'] = $player['p_first_name'];
	$_SESSION['last_name'] = $player['p_last_name'];
	$_SESSION['username'] = $player['p_username'];
	$_SESSION['email'] = $player['p_email'];
	$_SESSION['pref_history'] = $player['p_history'];
	$_SESSION['pref_theme'] = $player['p_theme'];
	$_SESSION['pref_auto_reload'] = $player['p_auto_reload'];
	$_SESSION['pref_max_games'] = $player['p_max_games'];
	$_SESSION['pref_show_last_move'] = ( "1" == $player['p_show_last_move'] ) ? true : false;
	$_SESSION['is_admin'] = ( $CFG_ROOT_ADMIN == $player['p_username'] || "1" == $player['p_is_admin'] ) ? true : false;

	$query = "
		UPDATE ".T_PLAYER."
		SET p_last_login = NOW( )
		WHERE p_id = '{$player['p_id']}'
	";
#	$mysql->query($query, __LINE__, __FILE__);

	// only regenerate the security info if we are loggin in
	// if it's a refresh login, skip this step
	if (isset($_POST['remember']) && ('' != $_POST['remember']))
	{
		// generate the security info
		session_regenerate_id(true);
		$ident = md5(uniqid(mt_rand( ), true));
		$token = md5(uniqid(mt_rand( ), true));
		$data  = $ident . $token;
		$data  = base64_encode($data);
		setcookie('WebChessData', $data, time( ) + (60 * 60 * 24 * 7)); // 1 week

		// save the new ident and token to the database
		$query = "
			UPDATE ".T_PLAYER."
			SET p_ident = '{$ident}'
				, p_token = '{$token}'
			WHERE p_id = '{$player['p_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);
	}

	$Message = new Message($_SESSION['player_id'], $_SESSION['is_admin']);
}
else
{
	if (!DEBUG)
	{
		echo '<script type="text/javascript">alert(\'Invalid Username and/or Password. Please try again.\'); window.location.replace(\'login.php\');</script>';
	}
	else
	{
		echo 'There was an error<br />POST: ';
		call($_POST);
		echo 'Query Results: ';
		call($player);
		echo 'MySQL error: ';
		call($mysql->fetch_error( ));
	}

	exit;
}

