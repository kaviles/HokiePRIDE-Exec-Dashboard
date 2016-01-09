<?php

// include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $libData = array('libid'=>$requestData['libid']);

    $libidCount = strlen($libData['libid']);
    if ($libidCount == 13)  {
        // $libData = escapeData($libData);

        return checkInBook($libData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Library ID is required."}';
    }
}

/**
* Checks books back into the library.
* Checks if book is checked out.
* If checked out, clears check out information on book.
* If not checked out, returns error about book not being checked out.
* TODO: Should book title be included in response messages?
*
* @param $libData is an associative array that contains the following:
* libid: The library id of the book to check in. <-- required
*
* @return A JSON formatted response string.
*/
function checkInBook($libData) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $q_libid = $libData['libid'];

        $qs = $mysqli->prepare("SELECT libid, status, patron_email FROM $db_table_library_books WHERE BINARY libid = ?");
        $qs->bind_param("s", $q_libid);
        $qs->bind_result($r_libid, $r_status, $r_pemail);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) { // Book found
            $qs->fetch();

            if ($r_status == 'CHECKED_OUT') {

                // No need for prepared statement, using safe data from database
                $q = "UPDATE $db_table_library_patrons SET itemcount = itemcount - 1 WHERE email='$r_pemail'";
                $request = $mysqli->query($q);

                if ($request == true) {
                    
                    $status = 'CHECKED_IN';
                    $testMember = 'testMember';
                    $timeStamp = getTimeStamp();

                    $qu = $mysqli->prepare("UPDATE $db_table_library_books SET status=?, status_by=?, status_timestamp=?, 
                    patron_firstname='', patron_lastname='', patron_email='' WHERE BINARY libid=?");
                    $qu->bind_param("ssss", $status, $admin, $timeStamp, $r_libid);
                    $qu_result = $qu->execute();
                    $qu->store_result();

                    if ($qu_result === true) {
                        $response = '{"responseCode":"1","message":"Book successfully checked IN."}';
                    }
                    else {
                        $response = '{"responseCode":"2","message":"Error! Book not successfully checked IN."}';
                    }

                    $qu->free_result();
                    $qu->close();
                }
                else {
                    $response = '{"responseCode":"2","message":"An error occurred with patron item count."}';
                }
            }
            else if ($r_status == 'CHECKED_IN') {
                $response = '{"responseCode":"0","message":"Book is not checked OUT."}';
            }
            else if ($r_status == 'CHECKED_REMOVED') {
                $response = '{"responseCode":"0","message":"Book is REMOVED."}';
            }
            else {
                $response = '{"responseCode":"2","message":"Book status issue discovered."}';
            }
        }
        else if ($qs_num_rows == 0) { // Book not found
            $response = '{"responseCode":"0","message":"Book not found."}';
        }
        else {
            $response = '{"responseCode":"2","message":"Error! Duplicate Library Book ID discovered!"}';
        }

        $qs->free_result();
        $qs->close();
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>