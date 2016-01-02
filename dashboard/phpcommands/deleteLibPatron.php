<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$email = $req_params['email'];

$response = deleteLibPatron($email);
echo json_encode($response);
?>