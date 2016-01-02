<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$email = $req_params['email'];
$reason = $req_params['reason'];

$response = removeLibPatron($email, $reason);
echo json_encode($response);
?>

