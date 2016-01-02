<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$isbn13 = $req_params['isbn13'];

$response = retrieveBookData($isbn13);
echo json_encode($response);
?>