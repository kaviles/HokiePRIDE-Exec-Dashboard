<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $bookData = array("title"=>$requestData['title'], "author"=>$requestData['author'],
    "pub"=>$requestData['pub'], "year"=>$requestData['year'], "isbn13"=>$requestData['isbn13'],
    "loc"=>$requestData['loc'], "dcc"=>$requestData['dcc'], "tags"=>$requestData['tags'],
    "covurl"=>$requestData['covurl'], "comms"=>$requestData['desc'], "libid"=>$requestData['libid']);

    if (isValidIsbn13($bookData['isbn13']) || isValidIsbn10($bookData['isbn13']) && count($bookData['libid']) == 13) {
        // $bookData = escapeData($bookData);

        return editBook($bookData);
    }
    else {
        return '{"responseCode":"0","message":"A valid ISBN and Library ID is required."}';
    }
}

/**
* Edits a book within the database.
*
* @param $bookData An associative array with all the data of the book to be edited.
* This array contains the following:
* title, author, pub, year, isbn, loc, dcc, tags, covurl, comms.
* Most of them should be self explanatory. 
* loc = library of congress call number
* dcc = dewey decimal call number
* covurl = cover url
* comms = comments
*
* @return A JSON formatted response string.
*/
function editBook($bookData) {
    
    include(__DIR__.'/../includes/dbtables.php');
    
    $response = '{"responseCode":"2","message":"Could not connect to database"}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $q_libid = $bookData['libid'];

        $qs = $mysqli->prepare("SELECT libid FROM $db_table_library_books WHERE BINARY libid=?");
        $qs->bind_param("s", $q_libid);
        $qs->bind_result($r_libid);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();

            $qu = $mysqli->prepare("UPDATE $db_table_library_books SET title=?, author=?, publisher=?, year=?, 
            loc=?, dcc=?, tags=?, covurl=?, comms=? WHERE BINARY libid=?");
            $qu->bind_param("ssssssssss", $bookData['title'], $bookData['author'], $bookData['pub'], $bookData['year'], 
                $bookData['loc'], $bookData['dcc'], $bookData['tags'], $bookData['covurl'], $bookData['comms'],
                $r_libid);
            $qu_result = $qu->execute();
            $qu->store_result();

            if ($qu_result === true) {
                $response = '{"responseCode":"1","message":"Book edit accepted!"}';
            }
            else {
                $response = '{"responseCode":"2","message":"Error! Book edit not accepted!"}';
            }

            $qu->free_result();
            $qu->close();
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