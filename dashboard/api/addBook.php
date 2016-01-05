<?php

include_once('../utility.php');

function handleRequestData($requestData) {
    $bookData = array("title"=>$requestData['title'], "author"=>$requestData['author'],
    "pub"=>$requestData['pub'], "year"=>$requestData['year'], "isbn13"=>$requestData['isbn13'],
    "loc"=>$requestData['loc'], "dcc"=>$requestData['dcc'], "tags"=>$requestData['tags'],
    "covurl"=>$requestData['covurl'], "comms"=>$requestData['desc'], "libid"=>$requestData['libid']);

    return addBook($bookData);
}

/**
* Adds a book to the database.
* Records date and time book was added.
*
* @param $bookData An associative array with all the data of the book to be added.
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
function addBook($bookData) {
    $response = '';

    $admin = 'testAdmin';

    if (isValidIsbn13($bookData['isbn13'])) {

        $response = '{"responseCode":"0","message":"Could not connect to database"}';

        $bookData = escapeData($bookData);
        $timeStamp = getTimeStamp();
        // $libid = uniqid();
        $status = 'CHECKED_IN';

        $mysqli = connectToDB();
        if ($mysqli) {
            $q = "INSERT INTO `library`(`libid`, `title`, `author`, `publisher`, `year`, `isbn13`, 
                `loc`, `dcc`, `tags`, `covurl`, `comms`, `added_timestamp`, `added_by`, `status`, `status_by`, `status_timestamp`) 
                VALUES ('{$bookData['libid']}', '{$bookData['title']}', '{$bookData['author']}', '{$bookData['pub']}', 
                    '{$bookData['year']}', '{$bookData['isbn13']}', '{$bookData['loc']}', '{$bookData['dcc']}', 
                    '{$bookData['tags']}', '{$bookData['covurl']}', '{$bookData['comms']}', '$timeStamp', 
                    '$admin', '$status', '$admin', '$timeStamp')";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"New book added!"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! New book not added!"}';
            }
        }
    }
    else {
        $response = '{"responseCode":"0","message":"Valid ISBN required"}';
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>