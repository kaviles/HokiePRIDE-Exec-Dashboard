<?php

include_once(__DIR__.'/../includes/utility.php');

function handleRequestData($requestData) {
    $bookData = array('libid'=>$requestData['libid']);

    $libidCount = strlen($bookData['libid']);
    if ($libidCount == 13) {
        $bookData = escapeData($bookData);

        return deleteBook($bookData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Library ID is required."}';
    }
}

/**
* Deletes books from library.
*
* @param $bookData is an associative array with the following: 
* $libid: The library id of the book to be deleted.
*
* @return A JSON formatted response string.
*/
function deleteBook($bookData) {
    
    include(__DIR__.'/../includes/dbtables.php');

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {
        $q_libid = $bookData['libid'];

        $qs = $mysqli->prepare("SELECT libid, status FROM $db_table_library_books WHERE libid = ?");
        $qs->bind_param("s", $q_libid);
        $qs->bind_result($r_libid, $r_status);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) { // Specific book exists, delete
            $qs->fetch();

            if ($r_status == 'CHECKED_REMOVED') {

                $qd = $mysqli->prepare("DELETE FROM $db_table_library_books WHERE libid = ?");
                $qd->bind_param("s", $r_libid);
                $qd_result = $qd->execute();
                $qd->store_result();

                if ($qd_result === true) {
                    $response = '{"responseCode":"1","message":"Book '.$r_libid.' deleted!"}';
                }
                else {
                    $response = '{"responseCode":"2","message":"Error! Book '.$r_libid.' not deleted!"}';
                }

                $qd->free_result();
                $qd->close();
            }
            else {
                $response = '{"responseCode":"0","message":"Remove Book before deleting."}';
            }
        }
        else { // Specific book does not exist, cannot delete
            $response = '{"responseCode":"0","message":"Book '.$q_libid.' not found!"}';
        }

        $qs->free_result();
        $qs->close();
    }

    disconnectFromDB($mysqli);
    return $response;
}

?>