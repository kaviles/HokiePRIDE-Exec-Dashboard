<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $libid = $requestData['libid'];

    return deleteBook($libid);
}

/**
* Deletes books from library.
*
* @param $libid the library id of the book.
*
* @return A JSON formatted response string.
*/
function deleteBook($libid) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();

    if ($mysqli) {
        $libid = $mysqli->real_escape_string($libid);

        $q = "SELECT * FROM library WHERE libid = '$libid'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) { // Board member not found, cannot delete
            $response = '{"responseCode":"0","message":"Library Book not found!","libid":"'.$libid.'"}';
        }
        else { // Specific book exists, delete
            $rowGet = $result->fetch_assoc();
            $q = "DELETE FROM library WHERE libid = '".$rowGet['libid']."'";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"Library Book deleted!","libid":"'.$rowGet['libid'].'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Library Book not deleted!","libid":"'.$rowGet['libid'].'"}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>