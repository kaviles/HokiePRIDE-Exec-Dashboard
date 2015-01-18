<?php
// include dbconnect.php
include_once('dbconnect.php');

/**
* Authorizes a user by checking $user with the usernames in the to the HokiePRIDE database.
*
* @param $user the username to authorize.
*
* @return NULL in all cases.
*/
function authorizeUser($user = '')
{
	$mysqli = connectToDB();

	$q = "SELECT * FROM exec_board WHERE pid = '".$user."'";
	$result = $mysqli->query($q);

	if ($result->num_rows == 0) 
	{
		// Row not found meaning user not found
		$message = date("Y-m-d H:i:s").' failed to authorize user '.$user.' as a HokiePRIDE exec board member.'."\n";
		error_log($message, 3, 'logs/general.log');
		disconnectFromDB($mysqli);
		return NULL;
	} 
	else 
	{
		// Row found meaning user was found
		$message = date("Y-m-d H:i:s").' authorized user '.$user.' as a HokiePRIDE exec board member.'."\n";
		error_log($message, 3, 'logs/general.log');
		disconnectFromDB($mysqli);
		return $result;
    }
}

?>