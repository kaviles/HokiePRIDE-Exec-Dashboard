<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$searchData = array("string"=>$req_params['string']);

$response = searchRequestAdmin($searchData);
echo json_encode($response);
?>