<?php
include_once('dashcommands.php');

header('Content-Type: application/json');


$patronData = array('firstname'=>$_REQUEST['firstname'], 'lastname'=>$_REQUEST['lastname'], 
	'phone'=>$_REQUEST['phone'], 'email'=>$_REQUEST['email']);

$response = addLibPatron($patronData);
echo json_encode($response);
?>

