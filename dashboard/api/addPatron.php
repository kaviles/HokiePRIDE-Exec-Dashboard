<?php

// include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $patronData = array('firstname'=>$requestData['firstname'], 'lastname'=>$requestData['lastname'], 
        'phone'=>$requestData['phone'], 'email'=>$requestData['email']);

    if (filter_var($patronData['email'], FILTER_VALIDATE_EMAIL))  {
        // $patronData = escapeData($patronData);

        return addPatron($patronData);
    }
    else {
        return '{"responseCode":"0","message":"A valid email is required."}';
    }
}

/**
* Adds a patron to the database.
* Checks if patron exists.
* If patron exists, return error about patron existing.
* If patron does not exist, add patron.
* Records date and time patron was added.
*
* @param $patronData an associative array that contains the following:
* firstname first name of library patron.
* lastname last name of library patron.
* phone phone number of library patron.
* email email address of library patron. <-- required
*
* @return A JSON formatted response string.
*/
function addPatron($patronData) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $q_email = $patronData['email'];

        $qs = $mysqli->prepare("SELECT email, status FROM $db_table_library_patrons WHERE email = ?");
        $qs->bind_param("s", $q_email);
        $qs->bind_result($r_email, $r_status);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 0) { // Add the new patron

            $timeStamp = getTimeStamp();
            $admin = 'testMember';
            $status = 'ADDED';

            $qi = $mysqli->prepare("INSERT INTO $db_table_library_patrons (firstname, lastname, phone, email, 
                added_by, added_timestamp, status, status_by, status_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $qi->bind_param("sssssssss", $patronData['firstname'], $patronData['lastname'], $patronData['phone'], 
                $q_email, $admin, $timeStamp, $status, $admin, $timeStamp);
            $qi_result = $qi->execute();
            $qi->store_result();

            if ($qi_result === true) {
                $response = '{"responseCode":"1","message":"New Patron '.$q_email.' added!"}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! New Patron '.$q_email.' not added!"}';
            }

            $qi->free_result();
            $qi->close();
        }
        else if ($qs_num_rows = 1) { // Patron already exists or was added but eventually removed
            $qs->fetch();

            if ($r_status == 'REMOVED') {

                $timeStamp = getTimeStamp();
                $admin = 'testMember';
                $status = 'ADDED';

                // TODO: Should removed info be updated here?
                $qu = $mysqli->prepare("UPDATE $db_table_library_patrons SET status=?, status_by=?, status_timestamp=? WHERE email=?");
                $qu->bind_param("ssss", $status, $admin, $timeStamp, $r_email);
                $qu_result = $qu->execute();
                $qu->store_result();

                if ($qu_result === true) {
                    $response = '{"responseCode":"1","message":"Patron '.$r_email.' re-added!"}';
                }
                else {
                    $response = '{"responseCode":"2","message":"Error! Patron '.$r_email.' not re-added!"}';
                }

                $qu->free_result();
                $qu->close();
            }
            else if ($r_status == 'ADDED') {
                $response = '{"responseCode":"0","message":"Patron '.$r_email.' is already added!"}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! Patron status issue discovered!"}';
            }
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