<?php
include_once('dbconnect.php');

/**
* Adds a user to the exec board table.
* All parameters except position should never be NULL.
*
* @param $fn first name of user.
* @param $ln last name of user.
* @param $pos position of user.
* @param $pid pid of user.
*
* @return A JSON formatted response string.
*/
function addEBMem($fn, $ln, $pos, $pid)
{
	$response = '';
	$mysqli = connectToDB();
	$q = "SELECT * FROM exec_board WHERE pid = '".$pid."'";
	$result = $mysqli->query($q);

	if ($result->num_rows == 0) // Add the new Eboard Member
	{
		$q = "INSERT INTO exec_board(pid, position, firstname, lastname) VALUES ('".$pid."', '".$pos."', '".$fn."', '".$ln."')";
		$result = $mysqli->query($q);

		if ($result == true)
		{
			$response = '{"responseCode":"1","message":"New Exec Board member added!","pid":"'.$pid.'"}';
		}
		else
		{
			$response = '{"responseCode":"0","message":"Error! Exec Board member not added!","pid":"'.$pid.'"}';
		}
	}
	else // pid already exists
	{
		$response = '{"responseCode":"0","message":"Exec Board member with that pid already exists!","pid":"'.$pid.'"}';
	}

	disconnectFromDB($mysqli);

	return $response;
}
?>