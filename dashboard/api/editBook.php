<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $bookData = array("title"=>$requestData['title'], "author"=>$requestData['author'],
    "pub"=>$requestData['pub'], "year"=>$requestData['year'], "isbn13"=>$requestData['isbn13'],
    "loc"=>$requestData['loc'], "dcc"=>$requestData['dcc'], "tags"=>$requestData['tags'],
    "covurl"=>$requestData['covurl'], "comms"=>$requestData['comms'], "libid"=>$requestData['libid']);

    return editBook($bookData);
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
    $response = '';

    $ebmem = 'testMember';

    $response = '{"responseCode":"0","message":"Could not connect to database"}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $bookData = escapeData($bookData);
        $q = "SELECT * FROM library WHERE libid='".$bookData['libid']."'";
        $result = $mysqli->query($q);
        if ($result) {

            $libid = $bookData['libid'];
            $title = $bookData['title'];
            $author = $bookData['author'];
            $publisher = $bookData['pub'];
            $year = $bookData['year'];
            $loc = $bookData['loc'];
            $dcc = $bookData['dcc'];
            $covurl = $bookData['covurl'];
            $tags = $bookData['tags'];
            $comms = $bookData['comms'];

            $q = "UPDATE library SET title='$title', author='$author', publisher='$publisher', 
            year='$year', loc='$loc', dcc='$dcc', tags='$tags', covurl='$covurl', comms='$comms'
            WHERE libid='$libid'";

            $result = $mysqli->query($q);
            if ($result == true) {
                $response = '{"responseCode":"1","message":"Book edit accepted!"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Book edit not accepted!"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>