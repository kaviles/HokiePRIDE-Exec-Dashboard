<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $bookData = array("libid" => $requestData['libid']);

    if (strlen($bookData['libid']) == 13) {
        $bookData = escapeData($bookData);

        return getBook($bookData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Library ID is required."}';
    }
}

/**
* Retrieves book data the library database for editing
*
* @param $bookData is an associative array with the following
* libid: the library id of the book that needs to be retrieved.
*
* @return A JSON formatted response string.
*/
function getBook($bookData) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $q_libid = $bookData['libid'];

        $qs = $mysqli->prepare("SELECT title, author, publisher, isbn13, year, 
            loc, dcc, tags, covurl, comms, libid FROM $db_table_library_books WHERE libid=?");
        $qs->bind_param("s", $q_libid);
        $qs->bind_result($r_title, $r_author, $r_publisher, $r_isbn13, $r_year, 
            $r_loc, $r_dcc, $r_tags, $r_covurl, $r_comms, $r_libid);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();

            $bookData = '"bookData":[';

            $bookData .=
            '{"title":"'.$r_title.'", "author":"'.$r_author.'", "publisher":"'.$r_publisher.'", "isbn13":"'.$r_isbn13.'",
            "year":"'.$r_year.'", "loc":"'.$r_loc.'", "dcc":"'.$r_dcc.'", "tag":"'.$r_tags.'", "covurl":"'.$r_covurl.'",
            "comms":"'.$r_comms.'", "libid":"'.$r_libid.'"}';

            $bookData .= ']';

            $response = '{"responseCode":"1","message":"Found Library Book",'.$bookData.'}';
        }
        else {
            $response = '{"responseCode":"0","message":"Book not found."}';
        }

        $qs->free_result();
        $qs->close();
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>