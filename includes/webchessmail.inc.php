<?php

// get required scripts
require_once 'config.inc.php';

function webchessMail($type, $msgTo, $move, $opponent, $game_id)
{
	global $CFG_MAILADDRESS, $CFG_MAINPAGE, $CFG_USEEMAIL;

	// make sure we can use email
	if ( ! $CFG_USEEMAIL)
	{
		return false;
	}

	// make sure there's an email address given
	if ('' == $msgTo && ! DEBUG)
	{
		return false;
	}

	// default subject header prefix
	$subject = 'WebChess: ';

	// load specific message and subject
	switch($type)
	{
		case 'test':
			$subject .= 'Test Message';
			$message = "Congratulations !!\n"
							 . "If you can see this message, you have successfully set up your email notification !\n"
							 . "Now, go to {$CFG_MAINPAGE} and play some chess !\n";
			break;
//------------------------------------------------------------------------------
		case 'invitation':
			$subject .= "{$opponent} has invited you to play a new game";
			$message = $opponent." has invited you to play a new game.\n"
							 . "Go to {$CFG_MAINPAGE} to either accept or deny this invitation.\n";
			break;
//------------------------------------------------------------------------------
		case 'withdrawal':
			$subject .= 'Invitation withdrawn';
			$message = "Your opponent, {$opponent} has withdrawn their invitation to play a new game.\n"
							 . "Go to {$CFG_MAINPAGE} to invite a player, or continue a game.\n";
			break;
//------------------------------------------------------------------------------
		case 'resignation':
			$subject .= "{$opponent} resigns on board {$game_id}.";
			$message = "Your opponent, {$opponent} has resigned the game on board {$game_id}.\n\n"
							 . "Go to {$CFG_MAINPAGE} to begin a new game.\n";
			break;
//------------------------------------------------------------------------------
		case 'move':
			$subject .= "{$opponent} moved {$move} on board {$game_id}.";
			$message = "Your opponent, {$opponent} has played the following move:\n"
							 . "{$move}\n\n"
							 . "It is your turn now\n"
							 . "Go to {$CFG_MAINPAGE} to play.\n";
			break;
//------------------------------------------------------------------------------
		case 'accepted':
			$subject .= 'Invitation Accepted';
			$message = $opponent." has accepted your invitation to play a new game.\n"
							 . "Go to {$CFG_MAINPAGE} to play.\n";
			break;
//------------------------------------------------------------------------------
		case 'declined':
			$subject .= 'Invitation Declined';
			$message = $opponent." has declined your invitation to play a new game.\n"
							 . "Go to {$CFG_MAINPAGE} to withdraw your offer.\n";
			break;
//------------------------------------------------------------------------------
		case 'deletewarning':
			$subject .= 'Game Deletion Warning';
			$message = "Your game ({$game_id}) is going to be deleted soon.\n"
							 . "Go to {$CFG_MAINPAGE} to review.\n";
			break;
//------------------------------------------------------------------------------
		case 'passupdate':
			$subject .= 'Password Reset Notification !!';
			$message = "Your password for WebChess has been reset.\n"
							 . "Your new password is: change!me\n\n"
							 . "Go to {$CFG_MAINPAGE} to log on now, select 'personal' from the menu,\n"
							 . "and change your password to something more secure.\n";
			break;
//------------------------------------------------------------------------------
		case 'wakeup':
			$subject .= 'Wake Up';
			$message = "Your opponent, {$opponent} has sent you a wake up call.\n"
							 . "It is your turn to move in game #{$game_id}.\n\n"
							 . "Go to {$CFG_MAINPAGE} to play.\n";
			break;
//------------------------------------------------------------------------------
		case 'checkmate':
			$subject .= 'Checkmate  =(';
			$message = "Your opponent, {$opponent} has placed you in checkmate.\n"
							 . "Better luck next time.\n\n"
							 . "Go to {$CFG_MAINPAGE} to begin a new game.\n";
			break;
		// ToDo: mailmsgundorequest.php ??
	}

	$message .= "\n\n----------------------------------------------\n"
						. "This message has been automatically sent\n"
						. 'by WebChess and should not be replied to.';

	$headers = "From: WebChess <{$CFG_MAILADDRESS}>\r\n";
	// Some MTAs may require for you to uncomment the following line. Do so if mail notification doesn't work
//  $headers = "To: {$msgTo}\r\n" . $headers;

	call('---MAIL---');
	call($msgTo);
	call($subject);
	call($message);
	call($headers);

	return mail($msgTo,$subject,$message,$headers);
}
?>