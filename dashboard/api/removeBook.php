<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
    $bookData = array("libid"=>$requestData['libid'], "reason"=>$requestData['reason']);

    if (strlen($bookData['libid']) == 13 && !empty($bookData['reason'])) {
        $bookData = escapeData($bookData);

        return removeBook($bookData);
    }
    else {
        return '{"responseCode":"0","message":"A valid Library ID and reason is required."}';
    }
}

/**
* Removes books from library.
*
* @param $bookData is an associative array that contains the following: 
* $libid: the library id of the book to remove.
* $reason: the reason the book was removed.
*
* @return A JSON formatted response string.
*/
function removeBook($bookData) {

    $response = '{"responseCode":"2","message":"Could not connect to database."}';

    $mysqli = connectToDB();
    if ($mysqli) {

        $q_libid = $bookData['libid'];

        $qs = $mysqli->prepare("SELECT libid, status FROM library_books WHERE libid = ?");
        $qs->bind_param("s", $q_libid);
        $qs->bind_result($r_libid, $r_status);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 1) {
            $qs->fetch();

            if ($r_status == 'CHECKED_IN') {

                $admin = 'testMember';
                $status = "CHECKED_REMOVED";
                $timeStamp = getTimeStamp();

                $qu = $mysqli->prepare("UPDATE library_books SET removed_by=?, removed_timestamp=?, removed_reason=?, 
                    status=?, status_by=?, status_timestamp=? WHERE libid=?");
                $qu->bind_param("sssssss", $admin, $timeStamp, $bookData['reason'], $status, $admin, $timeStamp, $r_libid);
                $qu_result = $qu->execute();
                $qu->store_result();

                if ($qu_result === true) {
                    $response = '{"responseCode":"1","message":"Book successfully removed."}';
                }
                else {
                    $response = '{"responseCode":"2","message":"Error! Book not successfully removed."}';
                }

                $qu->free_result();
                $qu->close();
            }
            else {
                $response = '{"responseCode":"0","message":"Check Book IN before removing."}';
            }
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