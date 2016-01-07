<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $libData = array('libid'=>$requestData['libid'], 'patronEmail'=>$requestData['patronEmail']);

    $libidCount = strlen($libData['libid']);
    if ($libidCount == 13 && filter_var($libData['patronEmail'], FILTER_VALIDATE_EMAIL)) {
        // $libData = escapeData($libData);

        return checkOutBook($libData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Patron Email and Library ID is required."}';
    }
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
* @param $libdata is an associative array that contains the following:
* libid: The library id of the book. <-- required
* patronEmail: The email of the patron to check the book out to. <-- required
*
* @return A JSON formatted response string.
*/
function checkOutBook($libData) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $q_libid = $libData['libid'];
        $q_patronEmail = $libData['patronEmail'];

        $qs_lb = $mysqli->prepare("SELECT libid, status FROM $db_table_library_books WHERE libid = ?");
        $qs_lb->bind_param("s", $q_libid);
        $qs_lb->bind_result($r_lb_libid, $r_lb_status);
        $qs_lb->execute();
        $qs_lb->store_result();

        $qs_lb_num_rows = $qs_lb->num_rows;

        if ($qs_lb_num_rows == 1) { // Library ID was found
            $qs_lb->fetch();

            if ($r_lb_status == 'CHECKED_IN') { // Book of Library ID was found and is checked IN

                $qs_lp = $mysqli->prepare("SELECT firstname, lastname, email, status FROM 
                    $db_table_library_patrons WHERE email = ?");
                $qs_lp->bind_param("s", $q_patronEmail);
                $qs_lp->bind_result($r_lp_pFname, $r_lp_pLname, $r_lp_pEmail, $r_lp_status);
                $qs_lp->execute();
                $qs_lp->store_result();

                $qs_lp_num_rows = $qs_lp->num_rows;
                if ($qs_lp_num_rows == 1) {
                    $qs_lp->fetch();

                    if ($r_lp_status == 'ADDED') { // Patron email was found

                        // No need for prepared statement, using safe data from database
                        $q = "UPDATE $db_table_library_patrons SET itemcount = itemcount + 1 WHERE email='$r_lp_pEmail'";
                        $request = $mysqli->query($q);

                        if ($request == true) {
                            $status = 'CHECKED_OUT';
                            $admin = 'testMember';
                            $timeStamp = getTimeStamp();

                            $qu = $mysqli->prepare("UPDATE $db_table_library_books SET status=?, status_by=?, status_timestamp=?, 
                                patron_firstname=?, patron_lastname=?, patron_email=? WHERE libid=?");
                            $qu->bind_param("sssssss", $status, $admin, $timeStamp, $r_lp_pFname, $r_lp_pLname, $r_lp_pEmail, $r_lb_libid);
                            $qu_result = $qu->execute();
                            $qu->store_result();

                            if ($qu_result === true) {
                                $response = '{"responseCode":"1", "message":"Book checked OUT to '.$r_lp_pEmail.'."}';
                            }
                            else {
                                $response = '{"responseCode":"2", "message":"Error! Book was not checked OUT."}';
                            }

                            $qu->free_result();
                            $qu->close();
                        }
                        else {
                            $response = '{"responseCode":"2","message":"Error occurred with patron item count."}';
                        }
                    }
                    else if ($r_lp_status == 'REMOVED') {
                        $response = '{"responseCode":"0","message":"Patron '.$r_lp_pEmail.' is REMOVED. Try re-adding them first."}';
                    }
                    else {
                        $response = '{"responseCode":"2","message":"Patron status issue discovered."}';
                    }
                }
                else if ($qs_lp_num_rows == 0) {
                    $response = '{"responseCode":"0","message":"Patron '.$r_lp_pEmail.' does not exist."}';
                }
                else {
                    $response = '{"responseCode":"2","message":"Error! Duplicate Patron emails discovered!"}';
                }

                $qs_lp->free_result();
                $qs_lp->close();
            }
            else if ($r_lb_status == 'CHECKED_OUT') {
                $response = '{"responseCode":"0","message":"Book is checked OUT."}';
            }
            else if ($r_lb_status == 'CHECKED_REMOVED') {
                $response = '{"responseCode":"0","message":"Book is REMOVED."}';
            }
            else {
                $response = '{"responseCode":"2","message":"Book status issue discovered."}';
            }
        }
        else { // Book not found
            $response = '{"responseCode":"0","message":"Book not found."}';
        }

        $qs_lb->free_result();
        $qs_lb->close();
    }
    
    disconnectFromDB($mysqli);
    return $response;
}

?>