<?php

// include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $searchData = array("string"=>$requestData['string']);

    $strings = explode(" ", $searchData['string']);

    $stringsLength = count($strings);
    if ($stringsLength < 4) {
        // $strings = escapeData($strings);

        return searchRequestAdmin($strings);
    }
    else {
        return '{"responseCode":"0","message":"Please submit a one to three word query."}';
    }
}

/**
* Checks books back into the library.
* Checks if book is checked out.
* If checked out, clears check out information on book.
* If not checked out, returns error about book not being checked out.
*
* @param $strings an array with zero to three phrases for searching.
*
* @return A JSON formatted response string with the following values.
* responseCode the response code, 0 for failure, 1 for success
* bookData data relevant to the library's books
* videoData data relevant to the library's dvds or vhss (TODO: Implement this)
*/
function searchRequestAdmin($strings) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"0","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $limit = count($strings);

        $qs = $mysqli->prepare("SELECT title, author, publisher, isbn13, isbn10, year, loc, dcc, 
            covurl, comms, tags, libid, status, status_by, status_timestamp, 
            patron_firstname, patron_lastname, patron_email 
            FROM $db_table_library_books WHERE title LIKE ? OR author LIKE ? OR 
            tags LIKE ? OR libid = ? OR patron_email = ? OR status = ? OR isbn13 = ? OR isbn10 = ? OR 
            comms LIKE ? OR publisher LIKE ? OR year = ? OR loc = ? OR dcc = ?");

        $resultsCount = 0;
        $books = '"bookData":[';

        for ($i = 0; $i < $limit; $i++) {
            $string = $strings[$i];
            $string_w = '%'.$strings[$i].'%';

            $qs->bind_param("sssssssssssss", $string_w, $string_w, $string_w, $string, 
                $string, $string, $string, $string, $string_w, $string_w, $string, 
                $string, $string);
            $qs->bind_result($r_title, $r_author, $r_publisher, $r_isbn13, $r_isbn10, $r_year, 
                $r_loc, $r_dcc, $r_covurl, $r_comms, $r_tags, $r_libid, 
                $r_status, $r_statusby, $r_statustime, $r_pfname, $r_plname, $r_pemail);
            $qs->execute();
            $qs->store_result();

            $qs_num_rows = $qs->num_rows;
            $resultsCount += $qs_num_rows;

            if ($qs_num_rows > 0) {

                // PHP 5.3 or higher
                // $result = $qs->get_result();
                // for($j = 0; $row = $result->fetch_assoc(); $j++) {
                for($j = 0; $qs->fetch(); $j++) {

                    if ($j > 0 || $i > 0) {
                        $books .= ',';
                    }

                    $books .= '{"title":"'.$r_title.'", "author":"'.$r_author.'", "publisher":"'.$r_publisher.'",
                    "isbn13":"'.$r_isbn13.'", "isbn10":"'.$r_isbn10.'", "year":"'.$r_year.'", "loc":"'.$r_loc.'", "dcc":"'.$r_dcc.'", 
                    "covurl":"'.$r_covurl.'", "comms":"'.$r_comms.'", "tags":"'.$r_tags.'", "libid":"'.$r_libid.'",
                    "status":"'.$r_status.'", "status_by":"'.$r_statusby.'", "status_time":"'.$r_statustime.'",
                    "fname":"'.$r_pfname.'", "lname":"'.$r_plname.'", "email":"'.$r_pemail.'"}';
                }
            }
        }

        $books .= "]";

        $qs->free_result();
        $qs->close();

        if ($resultsCount > 0) {
            $response = '{"responseCode":"1","message":"'.$resultsCount.' books(s) found",'.$books.'}';
        }
        else {
            $response = '{"responseCode":"0","message":"No results"}';
        }
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>