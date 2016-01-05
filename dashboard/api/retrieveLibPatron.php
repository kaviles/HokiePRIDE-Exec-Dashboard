<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $email = $requestData['email'];

    return retrieveLibPatron($email);
}

/**
* Retrieves the data on a library patron via email address.
*
* @param $email email of the library patron.
* @param $reason the reason the patron was removed.
*
* @return A JSON formatted response string.
*/
function retrieveLibPatron($email) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {
        $email = $mysqli->real_escape_string($email);
        $q = "SELECT * FROM library_patron WHERE email='$email' AND status='ADDED'";
        $request = $mysqli->query($q);

        if ($request->num_rows > 0) {
            $row = $request->fetch_assoc();
            
            $patronData = '"patronData":[';
            $patronData .=
            '{"firstname":"'.$row['firstname'].'",
            "lastname":"'.$row['lastname'].'",
            "phone":"'.$row['phone'].'",
            "email":"'.$row['email'].'"}';
            $patronData .= ']';

            $response = '{"responseCode":"1","message":"Found library patron '.$row['firstname'].' '.$row['lastname'].'",'.$patronData.'}';
        }
        else {
            $response = '{"responseCode":"0","message":"Error! Invalid library patron email"}';
        }
    }

    disconnectFromDB();
    return $response;
}

?>