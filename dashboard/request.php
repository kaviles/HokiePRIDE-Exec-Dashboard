<?php
header('Content-Type: application/json');

// $req_params = json_decode(implode("", $_GET), true);
$req_params = $_GET;

$requestType = $req_params['requestType'];

$removeArray = array("*", "'", "\"", "\\", "/", ".", ",");
$requestTypeSafe = str_replace($removeArray, "", $requestType);

$file = "api/".$requestTypeSafe.".php";

$response = '';
if (file_exists($file))
{
    include_once($file);
    $requestData = $req_params['requestData'];
    $response = handleRequestData($requestData);
}
else {
    $response = '{"responseCode":"0","message":"Invalid request command '.$requestTypeSafe.'"}';
}

echo json_encode($response);
?>