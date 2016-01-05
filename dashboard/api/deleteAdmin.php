<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $pid = $requestData['pid'];

    return deleteAdmin($pid);
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param pid the pid of the admin to delete from the database.
*
* @return A JSON formatted response string.
*/
function deleteAdmin($pid) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();

    if ($mysqli) {
        $pid = $mysqli->real_escape_string($pid);

        $q = "SELECT * FROM admins WHERE pid = '$pid'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) { // Admin not found, cannot delete
            $response = '{"responseCode":"0","message":"Admin not found!","admin":"'.$pid.'"}';
        }
        else { // Specific admin exists, delete
            $rowGet = $result->fetch_assoc();
            $q = "DELETE FROM admins WHERE pid = '".$rowGet['pid']."'";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"Admin deleted!","admin":"'.$rowGet['pid'].'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Admin not deleted!","admin":"'.$rowGet['pid'].'"}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>