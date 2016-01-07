<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $patronData = array("email" => $requestData['email']);

    if (filter_var($patronData['email'], FILTER_VALIDATE_EMAIL)) {
        $patronData = escapeData($patronData);

        return getPatron($patronData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Patron Email is required."}';
    }
}

/**
* Retrieves the data on a patron via email address.
*
* @param $patronData is an associative array with the following:
* email: The email of the library patron.
*
* @return A JSON formatted response string.
*/
function getPatron($patronData) {

    $response = '{"responseCode":"2","message":"Could not connect to database."}';
    
    $mysqli = connectToDB();
    if ($mysqli) {
        $q_email = $patronData['email'];

        $qs = $mysqli->prepare("SELECT firstname, lastname, phone, email, itemcount, 
            status, status_by, status_timestamp FROM library_patrons WHERE email=?");
        $qs->bind_param("s", $q_email);
        $qs->bind_result($r_firstname, $r_lastname, $r_phone, $r_email, 
            $r_itemcount, $r_status, $r_statusby, $r_statustime);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();
            
            $patronData = '"patronData":[';
            $patronData .= '{"firstname":"'.$r_firstname.'", "lastname":"'.$r_lastname.'", 
            "phone":"'.$r_phone.'", "email":"'.$r_email.'", "itemcount":"'.$r_itemcount.'",
            "status":"'.$r_status.'", "statusby":"'.$r_statusby.'", "statustime":"'.$r_statustime.'"}';
            $patronData .= ']';

            $response = '{"responseCode":"1","message":"Found library patron '.$r_email.'",'.$patronData.'}';
        }
        else if ($qs_num_rows == 0){
            $response = '{"responseCode":"0","message":"Patron not found."}';
        }
        else {
            $response = '{"responseCode":"2","message":"Error! Duplicate Patron emails discovered!"}';
        }

        $qs->free_result();
        $qs->close();
    }

    disconnectFromDB();
    return $response;
}

?>