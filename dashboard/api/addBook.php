<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $bookData = array("title"=>$requestData['title'], "author"=>$requestData['author'],
    "pub"=>$requestData['pub'], "year"=>$requestData['year'], 
    "isbn13"=>$requestData['isbn13'], "isbn10"=>$requestData['isbn10'],
    "loc"=>$requestData['loc'], "dcc"=>$requestData['dcc'], "tags"=>$requestData['tags'],
    "covurl"=>$requestData['covurl'], "comms"=>$requestData['desc']);

    if (isValidIsbn13($bookData['isbn13']) || isValidIsbn10($bookData['isbn10'])) {
        // $bookData = escapeData($bookData);

        return addBook($bookData);
    }
    else {
        return '{"responseCode":"0","message":"A valid ISBN13 or ISBN10 is required."}';
    }
}

/**
* Adds a book to the database.
* Records date and time book was added.
*
* @param $bookData An associative array with all the data of the book to be added.
* This array contains the following:
* title, author, pub, year, isbn13, loc, dcc, tags, covurl, comms.
* Most of them should be self explanatory. 
* loc = library of congress call number
* dcc = dewey decimal call number
* covurl = cover url
* comms = comments
*
* @return A JSON formatted response string.
*/
function addBook($bookData) {
    
    include(__DIR__.'/../includes/dbtables.php');
    
    $admin = 'testAdmin';

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $timeStamp = getTimeStamp();
        $status = 'CHECKED_IN';
        $libid = generateLibID();

        $qi = $mysqli->prepare("INSERT INTO $db_table_library_books (libid, title, author, publisher, year, isbn13, 
            isbn10, loc, dcc, tags, covurl, comms, added_timestamp, added_by, status, status_by, status_timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $qi->bind_param("sssssssssssssssss", $libid, $bookData['title'], $bookData['author'], 
            $bookData['pub'], $bookData['year'], $bookData['isbn13'], $bookData['isbn10'], 
            $bookData['loc'], $bookData['dcc'], $bookData['tags'], $bookData['covurl'], $bookData['comms'], 
            $timeStamp, $admin, $status, $admin, $timeStamp);
        $result = $qi->execute();
        $qi->store_result();

        // $shortTitle = substr($bookData['title'], 0, 16);

        if ($result === true) {
            $response = '{"responseCode":"1","message":"New book added! Library ID: '.$libid.'"}';
        }
        else {
            $response = '{"responseCode":"2","message":"Error! New book not added!"}';
        }
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>