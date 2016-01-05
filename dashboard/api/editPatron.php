<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $patronData = array("firstname"=>$requestData['firstname'], "lastname"=>$requestData['lastname'],
    "phone"=>$requestData['phone'], "email"=>$requestData['email']);

    return editPatron($patronData);
}

/**
* Retrieves the data on a library patron via email address.
*
* @param $patronData an associative array with the following values:
* firstname the firstname of the patron
* lastname the lastname of the patron
* phone the phone number of the patron
* email the email of the patron
*
* @return A JSON formatted response string.
*/
function editPatron($patronData) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {
        $patronData = escapeData($patronData);
        $email = $patronData['email'];
        $q = "SELECT * FROM library_patron WHERE email='$email' AND status='ADDED'";
        $request = $mysqli->query($q);

        if ($request->num_rows > 0) {

            $firstname = $patronData['firstname'];
            $lastname = $patronData['lastname'];
            $phone = $patronData['phone'];
            $email = $patronData['email'];

            $q = "UPDATE library_patron 
            SET firstname='$firstname', lastname='$lastname', phone='$phone' WHERE email='$email' AND status='ADDED'";

            $request = $mysqli->query($q);

            if ($request) {
                $response = '{"responseCode":"1","message":"Successfully edited Library Patron"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Did not edit Library Patron"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Error! Invalid library patron email"}';
        }
    }

    disconnectFromDB();
    return $response;
}

?>