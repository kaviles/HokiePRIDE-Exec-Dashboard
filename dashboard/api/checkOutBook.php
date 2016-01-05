<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $libid = $requestData['libid'];
    $patronEmail = $requestData['patronEmail'];

    return checkOutBook($libid, $patronEmail);
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
* @param $patronEmail the email of the patron checking out the book.
*
* @return A JSON formatted response string.
*/
function checkOutBook($libid, $patronEmail) {
    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    if ($mysqli) {

        $patronEmail = $mysqli->real_escape_string($patronEmail);
        $libid = $mysqli->real_escape_string($libid);

        $q = "SELECT * FROM library WHERE libid = '$libid'";

        $result_library = $mysqli->query($q);

        if ($result_library->num_rows > 0) { // Library ID was found
            $bookRow = $result_library->fetch_assoc();

            if ($bookRow['status'] == 'CHECKED_IN') { // Book of Library ID was found and is checked IN

                $q = "SELECT * FROM library_patron WHERE email = '$patronEmail' AND status = 'ADDED'";
                $result_patron = $mysqli->query($q);
                
                $patronRow = $result_patron->fetch_assoc();
                $timestamp = getTimeStamp();
                $p_firstname = $patronRow['firstname'];
                $p_lastname = $patronRow['lastname'];
                $p_email = $patronRow['email'];
                $p_phone = $patronRow['phone'];
                $l_libid = $bookRow['libid'];
                $testMember = 'testMember';

                if ($result_patron->num_rows > 0) { // Patron email was found
                    $q = "UPDATE library SET status='CHECKED_OUT', status_by='$testMember', status_timestamp='$timestamp', 
                    patron_firstname='$p_firstname', patron_lastname='$p_lastname',
                    patron_email='$p_email' WHERE libid='$l_libid'";

                    $result = $mysqli->query($q);

                    if ($result) {
                        $response = '{"responseCode":"1",
                        "message":"Book checked out to '.$p_firstname.' '.$p_lastname.'"}';
                    }
                    else {
                        $response = '{"responseCode":"0",
                        "message":"Error! Book not checked out to '.$p_firstname.' '.$p_lastname.'"}';
                    }
                }
                else if ($bookRow['status'] == 'CHECKED_REMOVED') {
                    $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Library Patron Email does not exist"}';
                }

            }
            else {
                $response = '{"responseCode":"0","message":"Book is not checked IN"}';
            }
        }
        else { // library patron already exists
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }
    
    disconnectFromDB($mysqli);

    return $response;
}

?>