<?php

// if (!isset($_SERVER['HTTPS'])) {
//     $response = '{"responseCode":0, "message":"Request must be over HTTPS."}';
//     echo json_encode($response);
//     die();
// }

if (!isset($_GET['requestType']) || empty($_GET['requestType'])) {
    $response = '{"responseCode":0, "message":"requestType parameter must be set."}';
    echo json_encode($response);
    die();
}

header('Content-Type: application/json');

include_once(__DIR__.'/includes/utility.php');

// cas_setup();

// if (cas_isAuthenticated()) {

//     $user = cas_getUser();
//     if (authorizeAdmin($user)) {
//         $requestFromAdmin = true;
//     }
//     else {
//         $requestFromAdmin = false;
//     }
// }
// else {
//     $user = "public";
//     $requestFromAdmin = false;
// }

$user = "testAdmin";
$requestFromAdmin = true;

if ($requestFromAdmin) {
    $apiArray = array(
        "addAdmin" => "addAdmin.php", "addBook" => "addBook.php", "addPatron" => "addPatron.php",
        "checkInBook" => "checkInBook.php", "checkOutBook" => "checkOutBook.php",
        "deleteAdmin" => "deleteAdmin.php", "deleteBook" => "deleteBook.php", "deletePatron" => "deletePatron.php",
        "editBook" => "editBook.php", "editPatron" => "editPatron.php",
        "generateLibID" => "generateLibID.php",
        "getAdmin" => "getAdmin.php","getBook" => "getBook.php", "getPatron" => "getPatron.php",
        "removeBook" => "removeBook.php", "removePatron" => "removePatron.php",
        "retrieveBookData" => "retrieveBookData.php", "searchRequest" => "searchRequestAdmin.php"
    );

    logMessage(__DIR__."/logs/request.log", "Admin Request [".$user."]: ".$_SERVER['REQUEST_URI']);
}
else {
    $apiArray = array(
        "searchRequest" => "searchRequestPublic.php"
    );

    logMessage(__DIR__."/logs/request.log", "Public Request, [".$user."]: ".$_SERVER['REQUEST_URI']);
}

$requestType = $_GET['requestType'];
$requestData = $_GET['requestData'];

if (array_key_exists($requestType, $apiArray)) {

    $file = __DIR__."/api/".$apiArray[$requestType];

    if (file_exists($file)) {
        include_once($file);
        $response = handleRequestData($requestData);
    }
    else {
        $response = '{"responseCode":2,"message":"Error accessing api: '.$requestType.'"}';
    }
}
else {
    $response = '{"responseCode":0,"message":"Invalid api request: '.$requestType.'"}';
}

logMessage(__DIR__."/logs/request.log", "Response: ".$response);

echo json_encode($response);
?>