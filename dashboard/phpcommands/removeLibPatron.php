<?php

include_once('utility.php');

function handleRequestData($requestData) {
    $email = $requestData['email'];
    $reason = $requestData['reason'];

    return removeLibPatron($email, $reason);
}

/**
* Removes library patrons from the database.
*
* @param $email email of the library patron.
* @param $reason the reason the patron was removed.
*
* @return A JSON formatted response string.
*/
function removeLibPatron($email, $reason) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $q = "SELECT * FROM library_patron WHERE email = '$email' AND status='ADDED'";

        $result = $mysqli->query($q);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $testMember = 'testMember';
            $timeStamp = getTimeStamp();

            $q = "UPDATE library_patron SET status='REMOVED', status_by='$testMember', status_timestamp='$timeStamp', 
            removed_by='$testMember', removed_timeStamp='$timeStamp', removed_reason='$reason' 
            WHERE email='$email'";

            $result = $mysqli->query($q);

            if ($result) {
                $response = '{"responseCode":"1","message":"Library Patron '.$row['firstname'].' '.$row['lastname'].' successfully removed"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Library Patron not successfully removed"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Patron Email"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>