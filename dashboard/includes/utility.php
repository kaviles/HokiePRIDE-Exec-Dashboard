<?php

include_once(__DIR__."/../../CAS-1.3.4/CAS-1.3.4/CAS.php");
  
date_default_timezone_set('America/New_York');

/**
* Connects to database.
*
* @return The connection to the database if successful. Null otherwise.
*/
function connectToDB()
{  
    // include database credentials for successful connection.
    include('dbconfig.php');

    // create connection
    $mysqli = new mysqli($db_host, $db_user, $db_password, $db_name);

    // PHP Website says OO version of connect_error is broken until PHP Version 5.3.0. 
    // Use procedural version instead.
    //if ($mysqli->connect_error())
    if (mysqli_connect_error($mysqli)) {
        // $message = date("Y-m-d H:i:s").' failed connect to database. '.$mysqli->connect_errno($mysqli).': '.mysqli_connect_error($mysqli);
        // error_log($message, 3, __DIR__.'/logs/dbconect.log');
        $message = 'Failed connect to database. '.$mysqli->connect_errno($mysqli).': '.mysqli_connect_error($mysqli);
        logMessage(__DIR__.'/logs/dbconect.log', $message);
        return NULL;
    }
    else {
        // $message = date("Y-m-d H:i:s").' successful connect to database.'."\n";
        // error_log($message, 3, __DIR__.'/logs/dbconnect.log');
        $message = 'Successful connect to database';
        logMessage(__DIR__.'/logs/dbconnect.log', $message);
        return $mysqli;
    }
}

/**
* Disconnects from database. This function may not be necessary since
* connectToDB returns its connection to the database or null but perhaps this would be helpful for 
* syntax and readability purposes.
*
* @param $mysqli a reference to a previously and successfully connected database.
*
* @return NULL in all cases.
*/
function disconnectFromDB($mysqli = NULL)
{
    if (!is_null($mysqli)) {
        //$database->disconnect();
        //mysqli_close($mysqli);
        if ($mysqli->close()) {
            $message = "Successful disconnect from database.";
            logMessage(__DIR__.'/logs/dbconnect.log', $message);
        }
        else {
            $message = "Unsuccessful disconnect from database.";
            logMessage(__DIR__.'/logs/dbconnect.log', $message);
        }
        // $message = date("Y-m-d H:i:s").' successful disconnect from database.'."\n";
        // error_log($message, 3, __DIR__.'/logs/dbconnect.log');
    }
    else {
        // $message = date("Y-m-d H:i:s").' attempting to disconnect from null database.'."\n";
        // error_log($message, 3, __DIR__.'/logs/dbconnect.log');
        $message = "Attempting to disconnect from null database.";
        logMessage(__DIR__.'/logs/dbconnect.log', $message);
    }
}

/*
************************************************
* UTILITY FUNCTIONS
************************************************
*/

function logMessage($filename, $message) {
    if (file_exists($filename)) {

        if (filesize($filename) < 1048576) {
            $f = fopen($filename, "a");
        }
        else {
            $f = fopen($filename, "w");
        }

        fwrite($f, getTimeStamp().": ".$message."\n", 8192);
        fclose($f);
    }
}

function cas_setup() {

    include(__DIR__.'/casconfig.php');

    // Uncomment to enable debugging
    // phpCAS::setDebug(__DIR__.'/logs/CAS.log');

    // Initialize phpCAS
    phpCAS::client(SAML_VERSION_1_1, $cas_host, $cas_port, $cas_context);

    // Download CAS client truststore from Middleware wiki
    // http://www.middleware.vt.edu/pubs/certs/cas_client_truststore.pem
    // and place in convenient location on filesystem
    phpCAS::setCasServerCACert(__DIR__.'/../../certificates/cascert.pem');

    // Handle SAML logout requests that emanate from the CAS host exclusively.
    // Failure to restrict SAML logout requests to authorized hosts could
    // allow denial of service attacks where at the least the server is
    // tied up parsing bogus XML messages.
    phpCAS::handleLogoutRequests(true, $cas_real_hosts);


    // // Force CAS authentication on any page that includes this file
    // phpCAS::forceAuthentication(true);
}

function cas_login() {
    logMessage(__DIR__."/logs/authorize.log", "Attempting to log into CAS.");
    // Force CAS authentication on any page that includes this file
    phpCAS::forceAuthentication(true);
}

function cas_logout() {
    logMessage(__DIR__."/logs/authorize.log", "Attempting to log out of CAS.");
    phpCAS::logout();
}

function cas_isAuthenticated() {
    return phpCAS::isAuthenticated();
}

function cas_getUser() {
    $user = phpCAS::getUser();

    if ($user) {
        $message = "Got CAS User: ".$user;
    }
    else {
        $message = "Did not get CAS User.";
    }

    logMessage(__DIR__."/logs/authorize.log", $message);

    return $user;
}

/**
* Authorizes a user by checking $user with the usernames in the to the database.
*
* @param $user the username to authorize.
*
* @return NULL in all cases.
*/
function authorizeAdmin($user = '')
{
    logMessage(__DIR__."/logs/authorize.log", "Authorizing user: ".$user.".");

    include(__DIR__."/dbtables.php");

    $mysqli = connectToDB();

    if ($mysqli) {
        $qs = $mysqli->prepare("SELECT pid FROM $db_table_library_admins WHERE pid = ?");
        $qs->bind_param("s", $user);
        $qs->bind_result($r_user);
        $qs->execute();
        $qs->store_result();

        $qs_num_rows = $qs->num_rows;

        if ($qs_num_rows == 0) {
            // Row not found meaning user not found
            $message = 'Did not authorize user '.$user.' as an Admin.';
            logMessage(__DIR__."/logs/authorize.log", $message);
            disconnectFromDB($mysqli);
            return false;
        } 
        else if ($qs_num_rows == 1) {
            // Row found meaning user was found
            $qs->fetch();
            $message = 'Authorized user '.$r_user.' as an Admin.'."\n";
            logMessage(__DIR__."/logs/authorize.log", $message);
            disconnectFromDB($mysqli);
            return true;
        }
        else {
            // Multiple rows found, error.
            $message = 'Error while authorizing user '.$user.' as an Admin.'."\n";
            logMessage(__DIR__."/logs/authorize.log", $message);
            disconnectFromDB($mysqli);
            return false;
        }

        $qs->free_result();
        $qs->close();
    }

    return NULL;  
}

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

    return $array;
}

function generateLibID() {

    $libid = md5(uniqid(), false);

    $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $findletters = array("a", "b", "c", "d", "e", "f");

    while (strlen($libid) > 13) {
        $libid = substr_replace($libid, "", rand(0, strlen($libid) - 1), 1);
    }

    for ($i = 0; $i < 13; $i++) {
        if (!is_numeric($libid[$i])) {
            for ($j = 0; $j < 6; $j++) {
                if ($libid[$i] == $findletters[$j]) {
                    $libid[$i] = $characters[rand(0, 51)];
                    break;
                }
            }  
        }
    }

    return $libid;
}

?>