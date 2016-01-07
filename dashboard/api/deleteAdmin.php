<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $adminData = array('pid'=>$requestData['pid']);
    
    if (!empty($adminData['pid']) && strpos($adminData['pid'], "@") === false) {
        $adminData = escapeData($adminData);

        return deleteAdmin($adminData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Admin pid is required."}';
    }
}

/**
* Deletes an admin from the database.
*
* @param adminData is an associative array with the following:
* pid: the pid of the admin to delete from the database.
*
* @return A JSON formatted response string.
*/
function deleteAdmin($adminData) {
    
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

        if ($qs_num_rows == 1) { // Specific admin exists, delete
            $qs->fetch();

            $qd = $mysqli->prepare("DELETE FROM $db_table_library_admins WHERE pid = ?");
            $qd->bind_param("s", $r_pid);
            $qd_result = $qd->execute();
            $qd->store_result();

            if ($qd_result === true) {
                $response = '{"responseCode":"1","message":"Admin '.$r_pid.' deleted!"}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! Admin '.$r_pid.' not deleted!"}';
            }

            $qd->free_result();
            $qd->close();
        }
        else { // Admin not found, cannot delete
            $response = '{"responseCode":"0","message":"Admin '.$q_pid.' not found!"}';
        }

        $qs->free_result();
        $qs->close();
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>