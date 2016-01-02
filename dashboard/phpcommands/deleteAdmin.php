<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$pid = $req_params['pid'];

$response = deleteAdmin($pid);
echo json_encode($response);
?>