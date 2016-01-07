<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $adminData = array("pid" => $requestData['pid']);

    if (!empty($adminData['pid']) && strpos($adminData['pid'], "@") === false) {
        $adminData = escapeData($adminData);

        return getAdmin($adminData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Admin pid is required."}';
    }
}

/**
* Gets all the data on and admin from the database table.
*
* @param adminData is an associative array that contains the following:
* pid: The pid of the admin whose data is being requested.
*
* @return A JSON formatted response string.
*/
function getAdmin($adminData) {
    
    include(__DIR__.'/../includes/dbtables.php');
    
    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {
        $q_pid = $adminData['pid'];

        if ($q_pid == 'all') { // get all admins

            // No user input, no need for prepared statement
            $q = "SELECT * FROM $db_table_library_admins";
            $result = $mysqli->query($q);

            $rowCount = $result->num_rows;
            if ($rowCount > 0) {  // admins found

                $admins = '"admins":[';

                for ($i = 0; $i < $rowCount; $i++) {
                    $row = $result->fetch_assoc();

                    $admins .= '{"firstname":"'.$row['firstname'].'","lastname":"'.$row['lastname'].'","pid":"'.$row['pid'].'"}';

                    if ($i < $rowCount - 1) {
                        $admins .= ',';
                    }
                }

                $admins .= ']';
                $response = '{"responseCode":"1","message":"'.$rowCount.' admin(s) found!",'.$admins.'}';
            }
            else {  // no admins
                $response = '{"responseCode":"0","message":"No admins found!"}';
            }
        }
        else { // look for specific admin


            $qs = $mysqli->prepared("SELECT pid, firstname, lastname, postion FROM $db_table_library_admins WHERE pid = ?");
            $qs->bind_param("s", $q_pid);
            $qs->bind_result($r_pid, $r_fname, $r_lname, $r_pos);
            $qs->execute();
            $qs->store_result();

            $qs_num_rows = $qs->num_rows;

            if ($result->num_rows == 1) { // Specific admin found
                $qs->fetch();

                $response = '{"responseCode":"1","message":"Admin '.$r_pid.' found!",
                "admin":["firstname":"'.$r_fname.'","lastname":"'.$r_lname.'","pid":"'.$r_pid.'"]}';
            }
            else { // Specific admin not found
                $response = '{"responseCode":"0","message":"Admin '.$q_pid.' not found!"}';
            }

            $qs->free_result();
            $qs->close();
        }
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>