<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$patronData = array('firstname'=>$req_params['firstname'], 'lastname'=>$req_params['lastname'], 
	'phone'=>$req_params['phone'], 'email'=>$req_params['email']);

$response = addLibPatron($patronData);
echo json_encode($response);
?>

