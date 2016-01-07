<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $adminData = array("firstname"=>$requestData['firstname'], "lastname"=>$requestData['lastname'],
    "position"=>$requestData['position'], "pid"=>$requestData['pid']);

    if (!empty($adminData['pid']) && strpos($adminData['pid'], "@") === false) {
        $adminData = escapeData($adminData);

        return addAdmin($adminData);
    }
    else {
        return '{"responseCode":"0","message":"A valid pid is required."}';
    }
}

/**
* Adds an admin to the library database table.
* All parameters can be null or empty except for pid.
*
* @param $adminData an associative array that contains the following:
* firstname: first name of user.
* lastname: last name of user.
* position: position of user.
* pid: pid of admin <-- required
*
* @return A JSON formatted response string.
*/
function addAdmin($adminData) {

    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $q_pid = $adminData['pid'];

        $qs = $mysqli->prepare("SELECT pid FROM $db_table_library_admins WHERE pid = ?");
        $qs->bind_param("s", $q_pid);
        $qs->bind_result($r_pid);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 0) { // Add the new admin

            $qi = $mysqli->prepare("INSERT INTO $db_table_library_admins (pid, position, firstname, lastname) VALUES (?, ?, ?, ?)");
            $qi->bind_param("ssss", $q_pid, $adminData['position'], $adminData['firstname'], $adminData['lastname']);
            $qi_result = $qi->execute();
            $qi->store_result();

            if ($qi_result === true) {
                $response = '{"responseCode":"1","message":"New Admin '.$q_pid.' added!"}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! New Admin '.$q_pid.' not added!"}';
            }

            $qi->free_result();
            $qi->close();
        }
        else if ($qs_num_rows == 1) { // Admin pid already exists
            $qs->fetch();
            $response = '{"responseCode":"0","message":"Admin '.$r_pid.' already exists!"}';
        }
        else {
            $response = '{"responseCode":"2","message":"Error! Duplicate Admin pids discovered!"}';
        }

        $qs->free_result();
        $qs->close();
    }
    
    disconnectFromDB($mysqli);
    return $response;
}

?>