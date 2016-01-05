<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $libid = $requestData['libid'];

    return retrieveDBBook($libid);
}

/**
* Retrieves book data the library database for editing
*
* @param $libid the library id of the book that needs to be retrieved.
*
* @return A JSON formatted response string.
*/
function retrieveDBBook($libid) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';

    $mysqli = connectToDB();

    if ($mysqli) {

        $libid = $mysqli->real_escape_string($libid);

        $q = "SELECT * FROM library WHERE libid='$libid'";
        $result = $mysqli->query($q);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $bookData = '"bookData":[';

            $bookData .=
            '{"title":"'.$row['title'].'",
            "author":"'.$row['author'].'",
            "publisher":"'.$row['publisher'].'",
            "isbn13":"'.$row['isbn13'].'",
            "year":"'.$row['year'].'",
            "loc":"'.$row['loc'].'",
            "dcc":"'.$row['dcc'].'",
            "tag":"'.$row['tags'].'",
            "covurl":"'.$row['covurl'].'",
            "comms":"'.$row['comms'].'",
            "libid":"'.$row['libid'].'"}';

            $bookData .= ']';

            $response = '{"responseCode":"1","message":"Found Library Book",'.$bookData.'}';
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>