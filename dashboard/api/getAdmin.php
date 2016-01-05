<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $pid = $requestData['pid'];

    return getAdmin($pid);
}

/**
* Gets a user from the database table.
* All parameters except position should never be NULL.
*
* @param member the member to get from the database. Can be
* all to get all the admins in the database.
*
* @return A JSON formatted response string.
*/
function getAdmin($pid) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';

    $mysqli = connectToDB();
    if ($mysqli) {
        if ($pid == 'all') { // get all admins

            $q = "SELECT * FROM admins";
            $result = $mysqli->query($q);

            $rowCount = $result->num_rows;
            if ($rowCount == 0) { // no admins
                $response = '{"responseCode":"0","message":"Error! No admins found!","pid":"'.$pid.'"}';
            }
            else { // admins found

                $admins = '"admins":[';

                for ($i = 0; $i < $rowCount; $i++) {
                    $row = $result->fetch_assoc();

                    $admins .= '{"firstname":"'.$row['firstname'].'","lastname":"'.$row['lastname'].'","pid":"'.$row['pid'].'"}';

                    if ($i < $rowCount - 1) {
                        $admins .= ',';
                    }
                }

                $admins .= ']';
                $response = '{"responseCode":"1","message":"'.$rowCount.' admin(s) found!","adminCount":"'.$rowCount.'",'.$admins.'}';  
            }
        }
        else { // look for specific admin
            $pid = $mysqli->real_escape_string($pid);
            $q = "SELECT * FROM admins WHERE pid = '$pid'";
            $result = $mysqli->query($q);

            if ($result->num_rows == 0) { // Specific admin not found
                $response = '{"responseCode":"0","message":"Admin '.$pid.' not found!"}';
            }
            else { // Specific admin exists
                $rowGet = $result->fetch_assoc();
                $response = '{"responseCode":"1","message":"Admin '.$pid.' found!",
                "admin":["firstname":"'.$rowGet['firstname'].'","lastname":"'.$rowGet['lastname'].'","pid":"'.$rowGet['pid'].'"]}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>