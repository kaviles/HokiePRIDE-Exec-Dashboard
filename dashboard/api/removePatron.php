<?php

// include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $patronData = array("email"=>$requestData['email'], "reason"=>$requestData['reason']);

    if (filter_var($patronData['email'], FILTER_VALIDATE_EMAIL) && !empty($patronData['reason'])) {
        // $patronData = escapeData($patronData);

        return removePatron($patronData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Patron Email and reason is required."}';
    }
}

/**
* Removes library patrons from the database.
*
* @param $patronData is an associative array with the following:
* email: email of the library patron to be removed.
* reason: the reason the patron was removed.
*
* @return A JSON formatted response string.
*/
function removePatron($patronData) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $q_email = $patronData['email'];

        $qs = $mysqli->prepare("SELECT email, itemcount, status FROM $db_table_library_patrons WHERE email = ?");
        $qs->bind_param("s", $q_email);
        $qs->bind_result($r_email, $r_itemcount, $r_status);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();

            if ($r_status == 'ADDED') {

                if ($r_itemcount === 0) {
                    $admin = 'testMember';
                    $status = 'REMOVED';
                    $timeStamp = getTimeStamp();

                    $qu = $mysqli->prepare("UPDATE $db_table_library_patrons SET status=?, status_by=?, status_timestamp=?, 
                    removed_by=?, removed_timeStamp=?, removed_reason=? WHERE email=?");
                    $qu->bind_param("sssssss", $status, $admin, $timeStamp, $admin, $timeStamp, $bookData['reason'], $r_email);
                    $qu_result = $qu->execute();
                    $qu->store_result();

                    if ($qu_result === true) {
                        $response = '{"responseCode":"1","message":"Patron '.$r_email.' successfully removed"}';
                    }
                    else {
                        $response = '{"responseCode":"2","message":"Error! Patron not successfully removed"}';
                    }

                    $qu->free_result();
                    $qu->close();
                }
                else
                {
                    $response = '{"responseCode":"0","message":"'.$r_itemcount.' item(s) checked out to patron '.$r_email.'."}';
                }
            }
            else if ($r_status == 'REMOVED') {
                $response = '{"responseCode":"0","message":"Patron '.$r_email.' is already removed."}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! Patron status issue discovered!"}';
            }
        }
        else if ($qs_num_rows == 0) {
            $response = '{"responseCode":"0","message":"Patron not found."}';
        }
        else {
            $response = '{"responseCode":"2","message":"Error! Duplicate Patron emails discovered!"}';
        }

        $qs->free_result();
        $qs->close();
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>