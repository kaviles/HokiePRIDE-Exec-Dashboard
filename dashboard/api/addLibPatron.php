<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $patronData = array('firstname'=>$requestData['firstname'], 'lastname'=>$requestData['lastname'], 
        'phone'=>$requestData['phone'], 'email'=>$requestData['email']);

    return addLibPatron($patronData);
}

/**
* Adds a patron to the database.
* Checks if patron exists.
* If patron exists, return error about patron existing.
* If patron does not exist, add patron.
* Records date and time patron was added.
*
* @param $patronData an associative array that contains the following:
* firstname first name of library patron.
* lastname last name of library patron.
* phone phone number of library patron.
* email email address of library patron. <- required
*
* @return A JSON formatted response string.
*/
function addLibPatron($patronData) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $patronData = escapeData($patronData);

        $q = "SELECT * FROM library_patron WHERE email = '{$patronData['email']}'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) { // Add the new board Member

            $timeStamp = getTimeStamp();
            $testMember = 'testMember';
            $q = "INSERT INTO `library_patron`(`firstname`, `lastname`, `phone`, `email`, 
                `added_by`, `added_timestamp`, `status`, `status_timestamp`) 
            VALUES ('{$patronData['firstname']}', '{$patronData['lastname']}', 
                '{$patronData['phone']}', '{$patronData['email']}', 
                '$testMember', '$timeStamp', 'ADDED', '$timeStamp')";

            $result = $mysqli->query($q);
            if ($result == true) {
                $response = '{"responseCode":"1","message":"New library patron added!"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Library patron not added!"}';
            }
        }
        else { // library patron already exists or was added but eventually removed
            $row = $result->fetch_assoc();
            if ($row['status'] == 'REMOVED') {

                $timeStamp = getTimeStamp();
                $testMember = 'testMember';
                $q = "UPDATE library_patron 
                SET status='ADDED', status_by='$testMember', status_timestamp='$timeStamp'
                WHERE email='{$patronData['email']}'";

                $result = $mysqli->query($q);
                if ($result == true) {
                    $response = '{"responseCode":"1","message":"New library patron added!"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Error! Library patron not added!"}';
                }
            }
            else {
                $response = '{"responseCode":"0","message":"Library patron already exists!"}';
            }
        }
    }
    
    disconnectFromDB($mysqli);

    return $response;
}

?>