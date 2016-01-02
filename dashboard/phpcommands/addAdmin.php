<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$adminData = array("firstname"=>$req_params['firstname'], "lastname"=>$req_params['lastname'],
	"position"=>$req_params['position'], "pid"=>$req_params['pid']);

$response = addAdmin($adminData);
echo json_encode($response);
?>

