<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
	$libid = $requestData['libid'];

	return checkInBook($libid);
}

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
    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $q = "SELECT * FROM library WHERE libid = '$libid'";

        $result_library = $mysqli->query($q);

        if ($result_library->num_rows > 0) {
            $row_library = $result_library->fetch_assoc();

            if ($row_library['status'] == 'CHECKED_OUT') {
                $testMember = 'testMember';
                $timeStamp = getTimeStamp();
                $l_libid = $row_library['libid'];

                $q = "UPDATE library SET status='CHECKED_IN', status_by='$testMember', status_timestamp='$timeStamp', 
                patron_firstname='', patron_lastname='', patron_email='' WHERE libid='$l_libid'";
                $result = $mysqli->query($q);

                if ($result) {
                    $response = '{"responseCode":"1","message":"Book successfully checked IN"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Error! Book not successfully checked IN"}';
                }
            }
            else if ($row_library['status'] == 'CHECKED_REMOVED') {
                $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Book is not checked OUT"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>