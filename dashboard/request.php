<?php
header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$requestType = $req_params['requestType'];

$removeArray = array("*", "'", "\"", "\\", ".", ",");
$requestTypeSafe = str_replace($removeArray, "", $requestType);

$file = "api/".$requestTypeSafe.".php";

$response = '';
if (file_exists($file))
{
    include_once($requestType.".php");
    $requestData = $req_params['requestData'];
    $response = handleRequestData($requestData);
}
else {
    echo json_encode('{"responseCode":"0","message":"Invalid request command"}');
}
?>