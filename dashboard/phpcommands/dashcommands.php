<?php
include_once('dbconnect.php');

/*
************************************************
* LIBRARY COMMANDS
************************************************
*/

/*
************************************************
* SETTING COMMANDS
************************************************
*/

/**
* Gets a user from the database table.
* All parameters except position should never be NULL.
*
* @param member the member to get from the database. Can be
* all to get all the members in the database.
*
* @return A JSON formatted response string.
*/
function getBoardMember($pid)
{
	$response = '';
	$mysqli = connectToDB();

	if ($pid == 'all') { // get all members
		$q = "SELECT * FROM exec_board";
		$result = $mysqli->query($q);

		$rowCount = $result->num_rows;
		if ($rowCount == 0) { // no board members
			$response = '{"responseCode":"0","message":"Error! No board members found!","pid":"'.$pid.'"}';
		}
		else { // board members found
			$response = '{"responseCode":"1","message":"Board members found!","memberCount":"'.$rowCount.'",';
			$response .= '"members":[';

			for ($i = 0; $i < $rowCount; $i++) {
				$row = $result->fetch_assoc();
				
				if ($i > 0) {
					$response .= ',';
				}

				$response .= '{"firstname":"'.$row['firstname'].'","lastname":"'.$row['lastname'].'","pid":"'.$row['pid'].'"}';
			}

			$response .= ']}';
		}
	}
	else { // look for specific member
		$q = "SELECT * FROM exec_board WHERE pid = '".$pid."'";
		$result = $mysqli->query($q);

		if ($result->num_rows == 0) { // Specific board member not found
			$response = '{"responseCode":"0","message":"Board member not found!","pid":"'.$pid.'"}';
		}
		else { // Specific board member exists
			$rowGet = $result->fetch_assoc();
			$response = '{"responseCode":"1","message":"Board member found!",
			"member":["firstname":"'.$rowGet['firstname'].'","lastname":"'.$rowGet['lastname'].'","pid":"'.$rowGet['pid'].'"]}';
		}
	}

	disconnectFromDB($mysqli);

	return $response;
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param member the member to delete from the database.
*
* @return A JSON formatted response string.
*/
function deleteBoardMember($pid)
{
	$response = '';
	$mysqli = connectToDB();

	$q = "SELECT * FROM exec_board WHERE pid = '".$pid."'";
	$result = $mysqli->query($q);

	if ($result->num_rows == 0) { // Board member not found, cannot delete
		$response = '{"responseCode":"0","message":"Board member not found!","member":"'.$pid.'"}';
	}
	else { // Specific board member exists, delete
		$rowGet = $result->fetch_assoc();
		$q = "DELETE FROM exec_board WHERE pid = '".$rowGet['pid']."'";
		$result = $mysqli->query($q);

		if ($result == true) {
			$response = '{"responseCode":"1","message":"Board member deleted!","member":"'.$rowGet['pid'].'"}';
		}
		else {
			$response = '{"responseCode":"0","message":"Error! Board member not deleted!","member":"'.$rowGet['pid'].'"}';
		}
	}

	disconnectFromDB($mysqli);

	return $response;
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param $fn first name of user.
* @param $ln last name of user.
* @param $pos position of user.
* @param $pid pid of user.
*
* @return A JSON formatted response string.
*/
function addBoardMember($fn, $ln, $pos, $pid)
{
	$response = '';
	$mysqli = connectToDB();
	$q = "SELECT * FROM exec_board WHERE pid = '".$pid."'";
	$result = $mysqli->query($q);

	if ($result->num_rows == 0) { // Add the new board Member
		$q = "INSERT INTO exec_board(pid, position, firstname, lastname) VALUES ('".$pid."', '".$pos."', '".$fn."', '".$ln."')";
		$result = $mysqli->query($q);

		if ($result == true) {
			$response = '{"responseCode":"1","message":"New board member added!","pid":"'.$pid.'"}';
		}
		else {
			$response = '{"responseCode":"0","message":"Error! New board member not added!","pid":"'.$pid.'"}';
		}
	}
	else { // pid already exists
		$response = '{"responseCode":"0","message":"Board member already exists!","pid":"'.$pid.'"}';
	}

	disconnectFromDB($mysqli);

	return $response;
}
?>