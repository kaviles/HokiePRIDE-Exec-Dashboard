<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $adminData = array("firstname"=>$requestData['firstname'], "lastname"=>$requestData['lastname'],
    "position"=>$requestData['position'], "pid"=>$requestData['pid']);

    return addAdmin($adminData);
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param $adminData an associative array that contains the following:
* firstname: first name of user.
* lastname: last name of user.
* position: position of user.
* pid: pid of admin
*
* @return A JSON formatted response string.
*/
function addAdmin($adminData) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $adminData = escapeData($adminData);
        $pid = $adminData['pid'];

        $q = "SELECT * FROM admins WHERE pid = '$pid'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) { // Add the new admin
            $q = "INSERT INTO `admins`(`pid`, `position`, `firstname`, `lastname`) 
            VALUES ('$pid', '{$adminData['position']}', '{$adminData['firstname']}', '{$adminData['lastname']}')";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"New admin added!","pid":"'.$pid.'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! New admin not added!","pid":"'.$pid.'"}';
            }
        }
        else { // pid already exists
            $response = '{"responseCode":"0","message":"Admin already exists!","pid":"'.$pid.'"}';
        }
    }
    
    disconnectFromDB($mysqli);

    return $response;
}

?>