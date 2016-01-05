<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $libid = $requestData['libid'];
    $reason = $requestData['reason'];

    return removeBook($libid, $reason);
}

/**
* Removes books from library.
*
* @param $libid the library id of the book.
* @param $reason the reason the book was removed.
*
* @return A JSON formatted response string.
*/
function removeBook($libid, $reason) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $q = "SELECT * FROM library WHERE libid = '$libid'";

        $result_library = $mysqli->query($q);

        if ($result_library->num_rows > 0) {
            $row_library = $result_library->fetch_assoc();

            if ($row_library['status'] == 'CHECKED_IN') {
                $testMember = 'testMember';
                $timeStamp = getTimeStamp();
                $l_libid = $row_library['libid'];

                $q = "UPDATE library SET removed_by='$testMember', removed_timestamp='$timeStamp', removed_reason='$reason', 
                status='CHECKED_REMOVED', status_by='$testMember', status_timestamp='$timeStamp' WHERE libid='$l_libid'";
                $result = $mysqli->query($q);

                if ($result) {
                    $response = '{"responseCode":"1","message":"Book successfully removed"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Error! Book not successfully removed"}';
                }
            }
            else {
                $response = '{"responseCode":"0","message":"Book is not checked IN"}';
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