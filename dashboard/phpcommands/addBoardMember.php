<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$bMemData = array("firstname"=>$_REQUEST['firstname'], "lastname"=>$_REQUEST['lastname'],
	"position"=>$_REQUEST['position'], "pid"=>$_REQUEST['pid']);

$response = addBoardMember($bMemData);
echo json_encode($response);
?>

