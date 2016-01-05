<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $searchData = array("string"=>$requestData['string']);

    return searchRequestAdmin($searchData);
}

/**
* Checks books back into the library.
* Checks if book is checked out.
* If checked out, clears check out information on book.
* If not checked out, returns error about book not being checked out.
*
* @param $arr an array with the following values:
* string the search string
*
* @return A JSON formatted response string with the following values.
* responseCode the response code, 0 for failure, 1 for success
* bookData data relevant to the library's books
* videoData data relevant to the library's dvds or vhss (TODO: Implement this)
*/
function searchRequestAdmin($arr) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $strings = explode(" ", $arr['string']);
        $stringsLength = count($strings);

        $limit = 0;
        if ($stringsLength > 3) {
            $limit = 3;
        }
        else {
            $limit = $stringsLength;
        }

        $q = "SELECT * FROM library WHERE";

        for ($i = 0; $i < $limit; $i++) {
            $q .= " title LIKE '%${strings[$i]}%' OR 
            author LIKE '%${strings[$i]}%' OR 
            tags LIKE '%${strings[$i]}%' OR 
            libid = '{$strings[$i]}' OR 
            patron_email = '{$strings[$i]}' OR 
            status = '{$strings[$i]}' OR 
            isbn13 = '{$strings[$i]}' OR 
            comms LIKE '%${strings[$i]}%' OR 
            publisher LIKE '%${strings[$i]}%' OR 
            year = '{$strings[$i]}' OR
            loc = '{$strings[$i]}' OR 
            dcc = '{$strings[$i]}'";

            if ($i + 1 < $limit) {
                $q .= " OR";
            }
        }

        $result = $mysqli->query($q);
        $rowCount = $result->num_rows;

        if ($rowCount > 0) {

            $books = '"bookData":[';

            for ($i = 0; $i < $rowCount; $i++) {
                $row = $result->fetch_assoc();

                $books .= '{"title":"'.$row['title'].'",
                "author":"'.$row['author'].'",
                "publisher":"'.$row['publisher'].'",
                "isbn13":"'.$row['isbn13'].'",
                "year":"'.$row['year'].'",
                "loc":"'.$row['loc'].'",
                "dcc":"'.$row['dcc'].'",
                "tag":"'.$row['tags'].'",
                "covurl":"'.$row['covurl'].'",
                "comms":"'.$row['comms'].'",
                "tags":"'.$row['tags'].'",
                "libid":"'.$row['libid'].'",
                "status":"'.$row['status'].'",
                "status_by":"'.$row['status_by'].'",
                "status_time":"'.$row['status_timestamp'].'",
                "fname":"'.$row['patron_firstname'].'",
                "lname":"'.$row['patron_lastname'].'",
                "email":"'.$row['patron_email'].'"}';

                if ($i + 1 < $rowCount) {
                    $books .= ", ";
                }
                else {
                    $books .= "]";
                }
            }

            $response = '{"responseCode":"1","message":"'.$rowCount.' books(s) found",'.$books.'}';
        }
        else {
            $response = '{"responseCode":"0","message":"No results"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

?>