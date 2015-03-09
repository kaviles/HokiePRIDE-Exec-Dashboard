<?php
include_once('dbconnect.php');


/*
************************************************
* UTILITY FUNCTIONS
************************************************
*/

function isValidIsbn10($isbn) {
    $check = 0;

    for ($i = 0; $i < 10; $i++) {
        if ('x' === strtolower($isbn[$i])) {
            $check += 10 * (10 - $i);
        } elseif (is_numeric($isbn[$i])) {
            $check += (int)$isbn[$i] * (10 - $i);
        } else {
            return false;
        }
    }

    return (0 === ($check % 11)) ? 1 : false;
}

function isValidIsbn13($isbn) {
    $check = 0;

    for ($i = 0; $i < 13; $i += 2) {
        $check += (int)$isbn[$i];
    }

    for ($i = 1; $i < 12; $i += 2) {
        $check += 3 * $isbn[$i];
    }

    return (0 === ($check % 10)) ? 2 : false;
}

function getTimeStamp() {

	return date_format(date_create(), 'Y-m-d H:i:s');
}

function escapeData($array) {

	$mysqli = connectToDB();

	if ($mysqli) {
		$arrLength = count($array);

		foreach ($array as $x => $x_val) {
			$array[$x] = $mysqli->real_escape_string($x_val);
		}
	}

	disconnectFromDB($mysqli);

	return $array; // I don't like that this has to be returned...
}

/*
************************************************
* LIBRARY COMMANDS
************************************************
*/

/**
* Checks books back into the library.
* Checks if book is checked out.
* If checked out, clears check out information on book.
* If not checked out, returns error about book not being checked out.
*
* @param $libid the library id of the book.
*
* @return A JSON formatted response string.
*/
function checkInBook($libid) {
	$response = '';
	$response = '{"responseCode":"0","message":"Functionality not yet implemented"}';

	return $response;
}

/**
* Checks books back into the library.
* Checks if patronid exists.
* If patronid exists, continue.
* If patronid does not exist, return error about patron not existing.
* Checks if book is already checked out.
* If checked out, returns error about book not being checked in.
* If not checked out, records date and time, checks book out to patron.
*
* @param $libid the library id of the book.
* @param $patronid the id of the patron checking out the book.
*
* @return A JSON formatted response string.
*/
function checkOutBook($libid, $patronid) {
	$response = '';
	$response = '{"responseCode":"0","message":"Functionality not yet implemented"}';

	return $response;
}

/**
* Adds a book to the database.
* Records date and time book was added.
* TODO: Library Book ID Check?
*
* @param $bookData An associative array with all the data of the book to be added.
* This array contains the following:
* title, author, pub, year, isbn, loc, dcc, tags, covurl, comms.
* Most of them should be self explanatory. 
* loc = library of congress call number
* dcc = dewey decimal call number
* covurl = cover url
* comms = comments
*
* @return A JSON formatted response string.
*/
function addBook($bookData) {
	$response = '';

	$ebmem = 'testMember';

	if (isValidIsbn13($bookData['isbn13'])) {

		$response = '{"responseCode":"0","message":"Could not connect to database"}';

		$bookData = escapeData($bookData);
		$timeStamp = getTimeStamp();
		$libid = uniqid();
		$status = 'IN';

		$mysqli = connectToDB();
		if ($mysqli) {
			$q = "INSERT INTO `library`(`libid`, `title`, `author`, `publisher`, `year`, `isbn13`, 
				`loc`, `dcc`, `tags`, `covurl`, `comms`, `added_timestamp`, `added_by`, `status`, `status_timestamp`) 
				VALUES ('$libid', '{$bookData['title']}', '{$bookData['author']}', '{$bookData['pub']}', 
					'{$bookData['year']}', '{$bookData['isbn13']}', '{$bookData['loc']}', '{$bookData['dcc']}', 
					'{$bookData['tags']}', '{$bookData['covurl']}', '{$bookData['comms']}', '$timeStamp', 
					'$ebmem', '$status', '$timeStamp')";
			$result = $mysqli->query($q);

			if ($result == true) {
				$response = '{"responseCode":"1","message":"New book added!"}';
			}
			else {
				$response = '{"responseCode":"0","message":"Error! New book not added!"}';
			}
		}
	}
	else {
		$response = '{"responseCode":"0","message":"Valid ISBN required"}';
	}

	disconnectFromDB($mysqli);
	return $response;
}

/**
* Retrieves book data from the internet for autofilling 
* with Google Books API and ISBNDB API.
*
* @param $isbn13 the 13 digit isbn number to query the databases for.
*
* @return A JSON formatted response string.
*/
function retrieveBookData($isbn13) {

	$response = '{"responseCode":"0","message":"No results"}';
	
	$url = "https://www.googleapis.com/books/v1/volumes?q=isbn:".$isbn13;
	$curl = curl_init();
	curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $url));

	$googResponse = curl_exec($curl);
	$googJson = json_decode($googResponse);

	$items = $googJson->items;
	$itemCount = $googJson->totalItems;

	$bookData = '"bookData":[';
	for ($i = 0; $i < $itemCount; $i++) {
		// Are there cases where we would want items beyond the first?
		$volInfo = $items[$i]->volumeInfo; 

		// response variables
		// Some of these are too long and need to be cut off
		$r_title = substr(($volInfo->subtitle) ? $volInfo->title.': '.$volInfo->subtitle : $volInfo->title, 0, 255);
		$r_author = ($volInfo->authors) ? $volInfo->authors : ''; // This might be an array of authors
		$r_publisher = substr(($volInfo->publisher) ? $volInfo->publisher : '', 0, 255);
		$r_year = ($volInfo->publishedDate) ? substr($volInfo->publishedDate, 0, 4) : '';
		$r_isbn13 = $isbn13;
		$r_loc = '';
		$r_dcc = '';
		$r_tag = ($volInfo->categories) ? $volInfo->categories : ''; // This will likely be an array;
		$r_covurl = substr(($volInfo->imageLinks->thumbnail) ? $volInfo->imageLinks->thumbnail : '', 0, 255);
		$r_desc = substr(($volInfo->description) ? $volInfo->description : '', 0, 255);
		// $r_libid = '';

		$authorString = '';

		for ($j = 0; $j < count($r_author); $j++) {
    		$authorString .= $r_author[$j];

    		if ($j != count($r_author) - 1) {
    			$authorString .= ', ';
    		}
	    }

		$authorString = substr($authorString , 0, 255);

	    $tagString = '';

	    for ($j = 0; $j < count($r_tag); $j++) {
    		$tagString .= $r_tag[$j];

    		if($j != count($r_tag) - 1) {
    			$tagString .= ', ';
    		}
	    }

	    $tagString = substr($tagString, 0, 255);

		// $url = "http://isbndb.com/api/books.xml?access_key=1YS12YZ5&index1=isbn&value1=".$isbn13;
		// curl_setopt_array($curl, array(
		// 	CURLOPT_RETURNTRANSFER => 1,
		// 	CURLOPT_URL => $url));

		// $isbndbResponse = curl_exec($curl);

	    $bookData .=
	    	'{"title":"'.$r_title.'",
	    	"author":"'.$authorString.'",
	    	"publisher":"'.$r_publisher.'",
	    	"isbn13":"'.$r_isbn13.'",
	    	"year":"'.$r_year.'",
	    	"loc":"'.$r_loc.'",
	    	"dcc":"'.$r_dcc.'",
	    	"tag":"'.$tagString.'",
	    	"covurl":"'.$r_covurl.'",
	    	"desc":"'.$r_desc.'"}';

	    if ($i < $itemCount - 1) {
	    	$bookData .= ', ';
	    }
	}

	$bookData .= ']';
	$response = '{"responseCode":"1","message":"'.$itemCount.' item(s) found",'.$bookData.'}';

	curl_close($curl);
	return $response;
}

/**
* Adds a patron to the database.
* Checks if patron exists.
* If patron exists, return error about patron existing.
* If patron does not exist, add patron.
* Records date and time patron was added.
*
* @param $firstname first name of library patron.
* @param $lastname last name of library patron.
* @param $phone phone number of library patron.
* @param $email email address of library patron.
* @param $patronid the patron id of the library patron.
*
* @return A JSON formatted response string.
*/
function addLibPatron($firstname, $lastname, $phone, $email, $patronid) {

	$response = '';
	$response = '{"responseCode":"0","message":"Functionality not yet implemented"}';

	return $response;
}

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
function getBoardMember($pid) {

	$response = '{"responseCode":"0","message":"Could not connect to database"}';

	$mysqli = connectToDB();
	if ($mysqli) {
		if ($pid == 'all') { // get all members

			$q = "SELECT * FROM exec_board";
			$result = $mysqli->query($q);

			$rowCount = $result->num_rows;
			if ($rowCount == 0) { // no board members
				$response = '{"responseCode":"0","message":"Error! No board members found!","pid":"'.$pid.'"}';
			}
			else { // board members found

				$members = '"members":[';

				for ($i = 0; $i < $rowCount; $i++) {
					$row = $result->fetch_assoc();

					$members .= '{"firstname":"'.$row['firstname'].'","lastname":"'.$row['lastname'].'","pid":"'.$row['pid'].'"}';

					if ($i < $rowCount - 1) {
						$members .= ',';
					}
				}

				$members .= ']';
				$response = '{"responseCode":"1","message":"'.$rowCount.' board member(s) found!","memberCount":"'.$rowCount.'",'.$members.'}';	
			}
		}
		else { // look for specific member
			$pid = $mysqli->real_escape_string($pid);
			$q = "SELECT * FROM exec_board WHERE pid = '$pid'";
			$result = $mysqli->query($q);

			if ($result->num_rows == 0) { // Specific board member not found
				$response = '{"responseCode":"0","message":"Board member '.$pid.' not found!"}';
			}
			else { // Specific board member exists
				$rowGet = $result->fetch_assoc();
				$response = '{"responseCode":"1","message":"Board member '.$pid.' found!",
				"member":["firstname":"'.$rowGet['firstname'].'","lastname":"'.$rowGet['lastname'].'","pid":"'.$rowGet['pid'].'"]}';
			}
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
function deleteBoardMember($pid) {

	$response = '{"responseCode":"0","message":"Could not connect to database"}';
	$mysqli = connectToDB();

	if ($mysqli) {
		$pid = $mysqli->real_escape_string($pid);

		$q = "SELECT * FROM exec_board WHERE pid = '$pid'";
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
	}

	disconnectFromDB($mysqli);

	return $response;
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param $bMemData an associative array that contains the following:
* firstname: first name of user.
* lastname: last name of user.
* position: position of user.
* pid: pid of board member.
*
* @return A JSON formatted response string.
*/
function addBoardMember($bMemData) {

	$response = '{"responseCode":"0","message":"Could not connect to database"}';
	
	$mysqli = connectToDB();
	if ($mysqli) {

		$bMemData = escapeData($bMemData);
		$pid = $bMemData['pid'];

		$q = "SELECT * FROM exec_board WHERE pid = '$pid'";
		$result = $mysqli->query($q);

		if ($result->num_rows == 0) { // Add the new board Member
			$q = "INSERT INTO `exec_board`(`pid`, `position`, `firstname`, `lastname`) 
			VALUES ('$pid', '{$bMemData['position']}', '{$bMemData['firstname']}', '{$bMemData['lastname']}')";
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
	}
	
	disconnectFromDB($mysqli);

	return $response;
}

?>