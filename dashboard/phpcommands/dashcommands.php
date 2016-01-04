<?php
include_once('dbconnect.php');


/*
************************************************
* UTILITY FUNCTIONS
************************************************
*/

function isValidIsbn10($isbn) {
    $check = 0;

    for ($i = 0; $i < 10; $i++) {
        if ('x' === strtolower($isbn[$i])) {
            $check += 10 * (10 - $i);
        } elseif (is_numeric($isbn[$i])) {
            $check += (int)$isbn[$i] * (10 - $i);
        } else {
            return false;
        }
    }

    return (0 === ($check % 11)) ? 1 : false;
}

function isValidIsbn13($isbn) {
    $check = 0;

    for ($i = 0; $i < 13; $i += 2) {
        $check += (int)$isbn[$i];
    }

    for ($i = 1; $i < 12; $i += 2) {
        $check += 3 * $isbn[$i];
    }

    return (0 === ($check % 10)) ? 2 : false;
}

function getTimeStamp() {

    return date_format(date_create(), 'Y-m-d H:i:s');
}

function escapeData($array) {

    $mysqli = connectToDB();

    if ($mysqli) {
        $arrLength = count($array);

        foreach ($array as $x => $x_val) {
            $array[$x] = $mysqli->real_escape_string($x_val);
        }
    }

    disconnectFromDB($mysqli);

    return $array; // I don't like that this has to be returned...
}

function generateLibID() {

    $libid = md5(uniqid(), false);

    while (strlen($libid) > 13) {
        $libid = substr_replace($libid, "", rand(0, strlen($libid) - 1), 1);
    }

    return $libid;
}

/*
************************************************
* LIBRARY COMMANDS
************************************************
*/

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

/**
* Checks books back into the library.
* Checks if book is checked out.
* If checked out, clears check out information on book.
* If not checked out, returns error about book not being checked out.
*
* @param $libid the library id of the book.
*
* @return A JSON formatted response string.
*/
function checkInBook($libid) {
    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $q = "SELECT * FROM library WHERE libid = '$libid'";

        $result_library = $mysqli->query($q);

        if ($result_library->num_rows > 0) {
            $row_library = $result_library->fetch_assoc();

            if ($row_library['status'] == 'CHECKED_OUT') {
                $testMember = 'testMember';
                $timeStamp = getTimeStamp();
                $l_libid = $row_library['libid'];

                $q = "UPDATE library SET status='CHECKED_IN', status_by='$testMember', status_timestamp='$timeStamp', 
                patron_firstname='', patron_lastname='', patron_email='' WHERE libid='$l_libid'";
                $result = $mysqli->query($q);

                if ($result) {
                    $response = '{"responseCode":"1","message":"Book successfully checked IN"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Error! Book not successfully checked IN"}';
                }
            }
            else if ($row_library['status'] == 'CHECKED_REMOVED') {
                $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Book is not checked OUT"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

/**
* Checks books back into the library.
* Checks if patronid exists.
* If patronid exists, continue.
* If patronid does not exist, return error about patron not existing.
* Checks if book is already checked out.
* If checked out, returns error about book not being checked in.
* If not checked out, records date and time, checks book out to patron.
*
* @param $libid the library id of the book.
* @param $patronEmail the email of the patron checking out the book.
*
* @return A JSON formatted response string.
*/
function checkOutBook($libid, $patronEmail) {
    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    if ($mysqli) {

        $patronEmail = $mysqli->real_escape_string($patronEmail);
        $libid = $mysqli->real_escape_string($libid);

        $q = "SELECT * FROM library WHERE libid = '$libid'";

        $result_library = $mysqli->query($q);

        if ($result_library->num_rows > 0) { // Library ID was found
            $bookRow = $result_library->fetch_assoc();

            if ($bookRow['status'] == 'CHECKED_IN') { // Book of Library ID was found and is checked IN

                $q = "SELECT * FROM library_patron WHERE email = '$patronEmail'";
                $result_patron = $mysqli->query($q);
                
                $patronRow = $result_patron->fetch_assoc();
                $timestamp = getTimeStamp();
                $p_firstname = $patronRow['firstname'];
                $p_lastname = $patronRow['lastname'];
                $p_email = $patronRow['email'];
                $p_phone = $patronRow['phone'];
                $l_libid = $bookRow['libid'];
                $testMember = 'testMember';

                if ($result_patron->num_rows > 0) { // Patron email was found
                    $q = "UPDATE library SET status='CHECKED_OUT', status_by='$testMember', status_timestamp='$timestamp', 
                    patron_firstname='$p_firstname', patron_lastname='$p_lastname',
                    patron_email='$p_email' WHERE libid='$l_libid'";

                    $result = $mysqli->query($q);

                    if ($result) {
                        $response = '{"responseCode":"1",
                        "message":"Book checked out to '.$p_firstname.' '.$p_lastname.'"}';
                    }
                    else {
                        $response = '{"responseCode":"0",
                        "message":"Error! Book not checked out to '.$p_firstname.' '.$p_lastname.'"}';
                    }
                }
                else if ($bookRow['status'] == 'CHECKED_REMOVED') {
                    $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Library Patron Email does not exist"}';
                }

            }
            else {
                $response = '{"responseCode":"0","message":"Book is not checked IN"}';
            }
        }
        else { // library patron already exists
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }
    
    disconnectFromDB($mysqli);

    return $response;
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

/**
* Removes books from library.
*
* @param $libid the library id of the book.
* @param $reason the reason the book was removed.
*
* @return A JSON formatted response string.
*/
function removeBook($libid, $reason) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $q = "SELECT * FROM library WHERE libid = '$libid'";

        $result_library = $mysqli->query($q);

        if ($result_library->num_rows > 0) {
            $row_library = $result_library->fetch_assoc();

            if ($row_library['status'] == 'CHECKED_IN') {
                $testMember = 'testMember';
                $timeStamp = getTimeStamp();
                $l_libid = $row_library['libid'];

                $q = "UPDATE library SET removed_by='$testMember', removed_timestamp='$timeStamp', removed_reason='$reason', 
                status='CHECKED_REMOVED', status_by='$testMember', status_timestamp='$timeStamp' WHERE libid='$l_libid'";
                $result = $mysqli->query($q);

                if ($result) {
                    $response = '{"responseCode":"1","message":"Book successfully removed"}';
                }
                else {
                    $response = '{"responseCode":"0","message":"Error! Book not successfully removed"}';
                }
            }
            else {
                $response = '{"responseCode":"0","message":"Book is not checked IN"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Book ID"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
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

/**
* Retrieves book data from the internet for autofilling 
* with Google Books API and ISBNDB API.
*
* @param $isbn13 the 13 digit isbn number to query the databases for.
*
* @return A JSON formatted response string.
*/
function retrieveBookData($isbn13) {

    $response = '{"responseCode":"0","message":"No results"}';
    $curl = curl_init();

    // Get Library of Congress and Dewey Decimal Numbers from Library of Congress database
    // SimpleXML also doesn't work for some reason...
    $url = "http://lx2.loc.gov:210/lcdb?version=2.0&operation=searchRetrieve&query=bath.isbn=".$isbn13."&maximumRecords=1";
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => "Content-Type:application/xml",
        CURLOPT_URL => $url));

    $locResponse = curl_exec($curl);

    $xml_parser = xml_parser_create();
    xml_parse_into_struct($xml_parser, $locResponse, $values, $index);
    xml_parser_free($xml_parser);

    $r_loc = '';
    $r_dcc = '';
    if ($values[$index['ZS:NUMBEROFRECORDS'][0]]['value'] > 0) {
        $DATAFIELD = $index['DATAFIELD'];

        $values_loc_start = -1;
        $values_loc_end = -1;
        $values_dcc_start = -1;
        $values_dcc_end = -1;

        // Get the start and end indices for the Datafields in the $values array
        // that contain lcc and dcc call numbers
        $k = 0;
        while (($values_loc_end == -1 || $values_dcc_end == -1) && $k < count($DATAFIELD)) {
            $valueIndex = $DATAFIELD[$k];
            $value = $values[$valueIndex];
            
            if ($value['type'] == 'open' && $value['attributes']['TAG'] == '050') {
                $values_loc_start = $valueIndex;
            }
            else if ($value['type'] == 'close' && $values_loc_end == -1 && $values_loc_start != -1) {
                $values_loc_end = $valueIndex;
            }

            if ($value['type'] == 'open' && $value['attributes']['TAG'] == '082') {
                $values_dcc_start = $valueIndex;
            }
            else if ($value['type'] == 'close' && $values_dcc_end == -1 && $values_dcc_start != -1) {
                $values_dcc_end = $valueIndex;
            }

            $k++;
        }

        // Fill out r_lcc
        if ($values_loc_start != -1 && $values_loc_end != -1) {

            $k = $values_loc_start + 1;
            while ($k < $values_loc_end) {

                if ($values[$k]['type'] == 'complete') {

                    $code = $values[$k]['attributes']['CODE'];
                    if ($code == 'a' || $code == 'b') {
                        if (strlen($r_loc) > 0) {
                            $r_loc .= ' ';
                        }

                        $r_loc .= $values[$k]['value'];
                    }
                }

                $k += 2;
            }
        }

        // Fill out r_dcc
        if ($values_dcc_start != -1 && $values_dcc_end != -1) {

            $k = $values_dcc_start + 1;
            while ($k < $values_dcc_end) {

                if ($values[$k]['type'] == 'complete') {

                    $code = $values[$k]['attributes']['CODE'];
                    if ($code == 'a' || $code == 'b') {
                        if (strlen($r_dcc) > 0) {
                            $r_dcc .= ' ';
                        }

                        $r_dcc .= $values[$k]['value'];
                    }
                }
                
                $k += 2;
            }
        }
    }
    
    // Get all other information from Google Books API
    $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:".$isbn13;
    curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $url));

    $googResponse = curl_exec($curl);
    $googJson = json_decode($googResponse);

    $items = $googJson->items;
    $itemCount = $googJson->totalItems;

    if ($itemCount > 0) {

        $bookData = '"bookData":[';
        for ($i = 0; $i < $itemCount; $i++) {
            // Are there cases where we would want items beyond the first?
            $volInfo = $items[$i]->volumeInfo; 

            // response variables
            // Some of these are too long and need to be cut off
            $r_title = substr(($volInfo->subtitle) ? $volInfo->title.': '.$volInfo->subtitle : $volInfo->title, 0, 255);
            $r_author = ($volInfo->authors) ? $volInfo->authors : ''; // This might be an array of authors
            $r_publisher = substr(($volInfo->publisher) ? $volInfo->publisher : '', 0, 255);
            $r_year = ($volInfo->publishedDate) ? substr($volInfo->publishedDate, 0, 4) : '';
            $r_isbn13 = $isbn13;
            // $r_loc = '';
            // $r_dcc = '';
            $r_tag = ($volInfo->categories) ? $volInfo->categories : ''; // This will likely be an array;
            $r_covurl = substr(($volInfo->imageLinks->thumbnail) ? $volInfo->imageLinks->thumbnail : '', 0, 255);
            $r_desc = substr(($volInfo->description) ? $volInfo->description : '', 0, 255);
            $r_libid = generateLibID();

            $authorString = '';

            for ($j = 0; $j < count($r_author); $j++) {
                $authorString .= $r_author[$j];

                if ($j != count($r_author) - 1) {
                    $authorString .= ', ';
                }
            }

            $authorString = substr($authorString , 0, 255);

            $tagString = '';

            for ($j = 0; $j < count($r_tag); $j++) {
                $tagString .= $r_tag[$j];

                if($j != count($r_tag) - 1) {
                    $tagString .= ', ';
                }
            }

            $tagString = substr($tagString, 0, 255);

            $r_title = str_replace('"', '', $r_title);
            $authorString = str_replace('"', '', $authorString);
            $r_publisher = str_replace('"', '', $r_publisher);
            $r_isbn13 = str_replace('"', '', $r_isbn13);
            $r_year = str_replace('"', '', $r_year);
            $r_loc = str_replace('"', '', $r_loc);
            $r_dcc = str_replace('"', '', $r_dcc);
            $tagString = str_replace('"', '', $tagString);
            $r_covurl = str_replace('"', '', $r_covurl);
            $r_desc = str_replace('"', '', $r_desc);

            $bookData .=
                '{"title":"'.$r_title.'",
                "author":"'.$authorString.'",
                "publisher":"'.$r_publisher.'",
                "isbn13":"'.$r_isbn13.'",
                "year":"'.$r_year.'",
                "loc":"'.$r_loc.'",
                "dcc":"'.$r_dcc.'",
                "tag":"'.$tagString.'",
                "covurl":"'.$r_covurl.'",
                "desc":"'.$r_desc.'",
                "libid":"'.$r_libid.'"}';

            if ($i < $itemCount - 1) {
                $bookData .= ', ';
            }
        }

        $bookData .= ']';
        $response = '{"responseCode":"1","message":"'.$itemCount.' item(s) found",'.$bookData.'}';
    }

    curl_close($curl);
    return $response;
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

/**
* Retrieves book data the library database for editing
*
* @param $holdingsRequest an associative array that contains the following:
* holdingStatuses the holdings of this status type to search for
* count the number of holdings to retrieve
* offset the number in the library database to start retrieving holdings from
*
* @return A JSON formatted response string.
*/
function retrieveHoldingsAdmin($holdingsRequest) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';

    $mysqli = connectToDB();

    if ($mysqli) {

        $q = "SELECT * FROM library WHERE status IN (";

        $arrCount = count($holdingsRequest['holdingStatuses']);

        for ($i=0; $i<$arrCount; $i++) {
            if ($i+1 < $arrCount) {
                $q = $q."'".$holdingsRequest['holdingStatuses'][$i]['type']."', ";
            }
            else {
                $q = $q."'".$holdingsRequest['holdingStatuses'][$i]['type']."') ";
            }
        }

        $q = $q."LIMIT {$holdingsRequest['count']} OFFSET {$holdingsRequest['offset']}";

        $result = $mysqli->query($q);
        $rowCount = $result->num_rows;

        if ($rowCount > 0) {

            $holdings = '"holdingsData":[';

            for ($i=0; $i<$rowCount; $i++) {
                $row = $result->fetch_assoc();

                if ($i+1 < $rowCount) {
                    $holdings .= '{"title":"'.$row['title'].'",
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
                    "email":"'.$row['patron_email'].'"}, ';
                }
                else {
                    $holdings .= '{"title":"'.$row['title'].'",
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
                    "email":"'.$row['patron_email'].'"}]';
                }
            }

            $response = '{"responseCode":"1","message":"'.$rowCount.' holding(s) found",'.$holdings.'}';
        }
        else {
            $response = '{"responseCode":"2","message":"No results"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
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
            if ($row['status'] == 'CHECKED_REMOVED') {

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

/**
* Retrieves the data on a library patron via email address.
*
* @param $email email of the library patron.
* @param $reason the reason the patron was removed.
*
* @return A JSON formatted response string.
*/
function retrieveLibPatron($email) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {
        $email = $mysqli->real_escape_string($email);
        $q = "SELECT * FROM library_patron WHERE email='$email' AND status='ADDED'";
        $request = $mysqli->query($q);

        if ($request->num_rows > 0) {
            $row = $request->fetch_assoc();
            
            $patronData = '"patronData":[';
            $patronData .=
            '{"firstname":"'.$row['firstname'].'",
            "lastname":"'.$row['lastname'].'",
            "phone":"'.$row['phone'].'",
            "email":"'.$row['email'].'"}';
            $patronData .= ']';

            $response = '{"responseCode":"1","message":"Found library patron '.$row['firstname'].' '.$row['lastname'].'",'.$patronData.'}';
        }
        else {
            $response = '{"responseCode":"0","message":"Error! Invalid library patron email"}';
        }
    }

    disconnectFromDB();
    return $response;
}

/**
* Retrieves the data on a library patron via email address.
*
* @param $patronData an associative array with the following values:
* firstname the firstname of the patron
* lastname the lastname of the patron
* phone the phone number of the patron
* email the email of the patron
*
* @return A JSON formatted response string.
*/
function editLibPatron($patronData) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {
        $patronData = escapeData($patronData);
        $email = $patronData['email'];
        $q = "SELECT * FROM library_patron WHERE email='$email' AND status='ADDED'";
        $request = $mysqli->query($q);

        if ($request->num_rows > 0) {

            $firstname = $patronData['firstname'];
            $lastname = $patronData['lastname'];
            $phone = $patronData['phone'];
            $email = $patronData['email'];

            $q = "UPDATE library_patron 
            SET firstname='$firstname', lastname='$lastname', phone='$phone' WHERE email='$email' AND status='ADDED'";

            $request = $mysqli->query($q);

            if ($request) {
                $response = '{"responseCode":"1","message":"Successfully edited Library Patron"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Did not edit Library Patron"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Error! Invalid library patron email"}';
        }
    }

    disconnectFromDB();
    return $response;
}

/**
* Removes library patrons from the database.
*
* @param $email email of the library patron.
* @param $reason the reason the patron was removed.
*
* @return A JSON formatted response string.
*/
function removeLibPatron($email, $reason) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();
    
    if ($mysqli) {
        $q = "SELECT * FROM library_patron WHERE email = '$email' AND status='ADDED'";

        $result = $mysqli->query($q);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $testMember = 'testMember';
            $timeStamp = getTimeStamp();

            $q = "UPDATE library_patron SET status='CHECKED_REMOVED', status_by='$testMember', status_timestamp='$timeStamp', 
            removed_by='$testMember', removed_timeStamp='$timeStamp', removed_reason='$reason' 
            WHERE email='$email'";

            $result = $mysqli->query($q);

            if ($result) {
                $response = '{"responseCode":"1","message":"Library Patron '.$row['firstname'].' '.$row['lastname'].' successfully removed"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Library Patron not successfully removed"}';
            }
        }
        else {
            $response = '{"responseCode":"0","message":"Invalid Library Patron Email"}';
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

/**
* Deletes library patrons from library.
*
* @param $email the library patron.
*
* @return A JSON formatted response string.
*/
function deleteLibPatron($email) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();

    if ($mysqli) {
        $email = $mysqli->real_escape_string($email);

        $q = "SELECT * FROM library_patron WHERE email = '$email'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) {
            $response = '{"responseCode":"0","message":"Library Patron not found!","email":"'.$email.'"}';
        }
        else {
            $rowGet = $result->fetch_assoc();
            $q = "DELETE FROM library_patron WHERE email = '".$rowGet['email']."'";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"Library Patron deleted!","email":"'.$rowGet['email'].'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Library Patron not deleted!","email":"'.$rowGet['email'].'"}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

/*
************************************************
* SETTING COMMANDS
************************************************
*/

/**
* Gets a user from the database table.
* All parameters except position should never be NULL.
*
* @param member the member to get from the database. Can be
* all to get all the admins in the database.
*
* @return A JSON formatted response string.
*/
function getAdmin($pid) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';

    $mysqli = connectToDB();
    if ($mysqli) {
        if ($pid == 'all') { // get all admins

            $q = "SELECT * FROM admins";
            $result = $mysqli->query($q);

            $rowCount = $result->num_rows;
            if ($rowCount == 0) { // no admins
                $response = '{"responseCode":"0","message":"Error! No admins found!","pid":"'.$pid.'"}';
            }
            else { // admins found

                $admins = '"admins":[';

                for ($i = 0; $i < $rowCount; $i++) {
                    $row = $result->fetch_assoc();

                    $admins .= '{"firstname":"'.$row['firstname'].'","lastname":"'.$row['lastname'].'","pid":"'.$row['pid'].'"}';

                    if ($i < $rowCount - 1) {
                        $admins .= ',';
                    }
                }

                $admins .= ']';
                $response = '{"responseCode":"1","message":"'.$rowCount.' admin(s) found!","adminCount":"'.$rowCount.'",'.$admins.'}';  
            }
        }
        else { // look for specific admin
            $pid = $mysqli->real_escape_string($pid);
            $q = "SELECT * FROM admins WHERE pid = '$pid'";
            $result = $mysqli->query($q);

            if ($result->num_rows == 0) { // Specific admin not found
                $response = '{"responseCode":"0","message":"Admin '.$pid.' not found!"}';
            }
            else { // Specific admin exists
                $rowGet = $result->fetch_assoc();
                $response = '{"responseCode":"1","message":"Admin '.$pid.' found!",
                "admin":["firstname":"'.$rowGet['firstname'].'","lastname":"'.$rowGet['lastname'].'","pid":"'.$rowGet['pid'].'"]}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param pid the pid of the admin to delete from the database.
*
* @return A JSON formatted response string.
*/
function deleteAdmin($pid) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    $mysqli = connectToDB();

    if ($mysqli) {
        $pid = $mysqli->real_escape_string($pid);

        $q = "SELECT * FROM admins WHERE pid = '$pid'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) { // Admin not found, cannot delete
            $response = '{"responseCode":"0","message":"Admin not found!","admin":"'.$pid.'"}';
        }
        else { // Specific admin exists, delete
            $rowGet = $result->fetch_assoc();
            $q = "DELETE FROM admins WHERE pid = '".$rowGet['pid']."'";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"Admin deleted!","admin":"'.$rowGet['pid'].'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! Admin not deleted!","admin":"'.$rowGet['pid'].'"}';
            }
        }
    }

    disconnectFromDB($mysqli);

    return $response;
}

/**
* Adds a user to the database table.
* All parameters except position should never be NULL.
*
* @param $adminData an associative array that contains the following:
* firstname: first name of user.
* lastname: last name of user.
* position: position of user.
* pid: pid of admin
*
* @return A JSON formatted response string.
*/
function addAdmin($adminData) {

    $response = '{"responseCode":"0","message":"Could not connect to database"}';
    
    $mysqli = connectToDB();
    if ($mysqli) {

        $adminData = escapeData($adminData);
        $pid = $adminData['pid'];

        $q = "SELECT * FROM admins WHERE pid = '$pid'";
        $result = $mysqli->query($q);

        if ($result->num_rows == 0) { // Add the new admin
            $q = "INSERT INTO `admins`(`pid`, `position`, `firstname`, `lastname`) 
            VALUES ('$pid', '{$adminData['position']}', '{$adminData['firstname']}', '{$adminData['lastname']}')";
            $result = $mysqli->query($q);

            if ($result == true) {
                $response = '{"responseCode":"1","message":"New admin added!","pid":"'.$pid.'"}';
            }
            else {
                $response = '{"responseCode":"0","message":"Error! New admin not added!","pid":"'.$pid.'"}';
            }
        }
        else { // pid already exists
            $response = '{"responseCode":"0","message":"Admin already exists!","pid":"'.$pid.'"}';
        }
    }
    
    disconnectFromDB($mysqli);

    return $response;
}

?>