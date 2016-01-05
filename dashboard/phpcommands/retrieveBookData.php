<?php

include_once('utility.php');

function handleRequestData($requestData) {
    $isbn13 = $requestData['isbn13'];

    return retrieveBookData($isbn13);
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

?>