<?php require_once '../includes/config.inc.php'; ?>

function validatepersonal( )
{
	// check that all name fields are filled
	if (document.personal.txtFirstName.value.match(/^\s*$/)
	 || document.personal.txtLastName.value.match(/^\s*$/)
	 <?php
		if ($CFG_CHANGEUSERNAME)
		echo "       || document.personal.txtUsername.value.match(/^\s*$/)";
	 ?>)
	{
		alert("Sorry, all name fields are required and must be filled out.");
		return;
	}

	// check for either of the new password fields
	if (document.personal.pwdPassword.value != ''
	|| document.personal.pwdPassword2.value != '')
	{
		// check for all of the password fields
		if (document.personal.pwdOldPassword.value.match(/\s|^\s*$/)
		 || document.personal.pwdPassword.value.match(/\s|^\s*$/)
		 || document.personal.pwdPassword2.value.match(/\s|^\s*$/))
		{
			alert("Sorry, all password fields must be filled out to change your password.\nNo spaces are allowed in the passwords.");
			return;
		}
	}

	// if both are blank, they are still equal
	if (document.personal.pwdPassword.value == document.personal.pwdPassword2.value)
	{
		// make sure not to run the md5 sum if the passwords are blank... bad, very bad
		if (document.personal.pwdPassword.value.substring(0,5) != '!md5!'
			&& document.personal.pwdPassword.value != '')
		{
			document.personal.pwdOldPassword.value = '!md5!' + hex_md5(document.personal.pwdOldPassword.value);
			document.personal.pwdPassword.value    = '!md5!' + hex_md5(document.personal.pwdPassword.value);
			document.personal.pwdPassword2.value   = '!md5!' + hex_md5(document.personal.pwdPassword2.value);
		}

		document.personal.submit( );
	}
	else
	{
		alert("Sorry, the two password fields don't match.  Please try again.");
	}
}


function validateInvite( )
{
	if (document.challenge.txtId960)
	{
		if (document.challenge.txtId960.value >= 960 || document.challenge.txtId960.value < 0)
		{
			alert("Sorry, that is an invalid Chess960 ID.\nPlease use a number between 0 - 959.");
			document.challenge.txtId960.value = '';
			document.challenge.txtId960.focus( );
			return;
		}
		else
		{
			document.challenge.submit( );
		}
	}
	else
	{
		document.challenge.submit( );
	}
}


function withdrawrequest(game_id)
{
	document.withdraw.game_id.value = game_id;
	document.withdraw.submit( );
}


function sendresponse(responseType, message_from, game_id)
{
	document.response.respond.value = responseType;
	document.response.message_from.value = message_from;
	document.response.game_id.value = game_id;
	document.response.submit( );
}


function loadGame(game_id)
{
	document.games.action = 'chess.php';

	if (document.games.rdoShare[0].checked)
		document.games.action = 'opppass.php';

	document.games.game_id.value = game_id;
	document.games.submit( );
}


function watchGame(game_id)
{
	document.games.action = 'watchgame.php';
	document.games.game_id.value = game_id;
	document.games.submit( );
}


function viewmessage(game_id)
{
	document.messageview.messageid.value = game_id;
	document.messageview.submit( );
}

<?php if ($CFG_USEEMAIL) { ?>
function testEmail( )
{
	document.preferences.todo.value = "TestEmail";
	document.preferences.submit( );
}
<?php } ?>