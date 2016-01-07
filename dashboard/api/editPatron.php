<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $patronData = array("firstname"=>$requestData['firstname'], "lastname"=>$requestData['lastname'],
    "phone"=>$requestData['phone'], "email"=>$requestData['email']);

    if (filter_var($patronData['email'], FILTER_VALIDATE_EMAIL)) {
        $patronData = escapeData($patronData);

        return editPatron($patronData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Patron Email is required."}';
    }
}

/**
* Edits the data on a library patron via email address.
*
* @param $patronData an associative array with the following values:
* firstname: the firstname of the patron
* lastname: the lastname of the patron
* phone: the phone number of the patron
* email: the email of the patron <-- required
*
* @return A JSON formatted response string.
*/
function editPatron($patronData) {

    $response = '{"responseCode":"2","message":"Could not connect to database."}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $q_email = $patronData['email'];

        $qs = $mysqli->prepare("SELECT email FROM library_patrons WHERE email=?");
        $qs->bind_param("s", $q_email);
        $qs->bind_result($r_email);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();

            $qu = $mysqli->prepare("UPDATE library_patrons SET firstname=?, lastname=?, phone=? WHERE email=?");
            $qu->bind_param("ssss", $patronData['firstname'], $patronData['lastname'], 
                $patronData['phone'], $patronData['email']);
            $qu_result = $qu->execute();
            $qu->store_result();

            if ($qu_result === true) {
                $response = '{"responseCode":"1","message":"Patron edit accepted."}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! Patron edit not accepted!"}';
            }
        }
        else if ($qs_num_rows == 0) {
            $response = '{"responseCode":"0","message":"Patron not found."}';
        }
        else {
            $response = '{"responseCode":"2","message":"Error! Duplicate Patron emails discovered!"}';
        }
    }

    disconnectFromDB();
    return $response;
}

?>