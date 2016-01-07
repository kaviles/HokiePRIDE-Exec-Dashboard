<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $patronData = array('email'=>$requestData['email']);

    if (filter_var($patronData['email'], FILTER_VALIDATE_EMAIL)) {
        $patronData = escapeData($patronData);

        return deletePatron($patronData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Patron Email is required."}';
    }
}

/**
* Deletes library patrons from library.
*
* @param $patronData is an associative array with the following:
* email: the email of the patron to be deleted from the database. <-- required
*
* @return A JSON formatted response string.
*/
function deletePatron($patronData) {

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $q_email = $patronData['email'];

        $qs = $mysqli->prepare("SELECT email, status FROM library_patrons WHERE email = ?");
        $qs->bind_param("s", $q_email);
        $qs->bind_result($r_email, $r_status);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();

            if ($r_status == 'REMOVED')
            {
                $qd = $mysqli->prepare("DELETE FROM library_patrons WHERE email = ?");
                $qd->bind_param("s", $r_email);
                $qd_result = $qd->execute();
                $qd->store_result();

                if ($qd_result === true) {
                    $response = '{"responseCode":"1","message":"Patron '.$r_email.' deleted!"}';
                }
                else {
                    $response = '{"responseCode":"2","message":"Error! Patron '.$r_email.' not deleted!"}';
                }

                $qd->free_result();
                $qd->close();
            }
            else if ($r_status == 'ADDED') {
                $response = '{"responseCode":"0","message":"Remove Patron '.$r_email.' before deleting."}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! Patron status issue discovered!"}';
            }
        }
        else if ($qs_num_rows == 0) {
            $response = '{"responseCode":"0","message":"Patron '.$r_email.' not found!"}';
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