<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$libid = $req_params['libid'];

$response = checkInBook($libid);
echo json_encode($response);
?>

