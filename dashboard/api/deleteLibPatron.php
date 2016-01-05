<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $email = $requestData['email'];

    return deleteLibPatron($email);
}

/**
* Deletes library patrons from library.
*
* @param $email the library patron.
*
* @return A JSON formatted response string.
*/
function deleteLibPatron($email) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();

    if ($mysqli) {
        $email = $mysqli->real_escape_string($email);

        $q = "SELECT * FROM library_patron WHERE email = '$email'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) {
            $response = '{"responseCode":"0","message":"Library Patron not found!","email":"'.$email.'"}';
        }
        else {
            $rowGet = $result->fetch_assoc();
            $q = "DELETE FROM library_patron WHERE email = '".$rowGet['email']."'";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"Library Patron deleted!","email":"'.$rowGet['email'].'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Library Patron not deleted!","email":"'.$rowGet['email'].'"}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>