<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$libid = $req_params['libid'];
$patronEmail = $req_params['patronEmail'];

$response = checkOutBook($libid, $patronEmail);
echo json_encode($response);
?>
