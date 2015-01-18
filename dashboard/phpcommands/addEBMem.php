<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$firstname = $_REQUEST['firstname'];
$lastname = $_REQUEST['lastname'];
$position = $_REQUEST['position'];
$pid = $_REQUEST['pid'];

$response = addEBMem($firstname, $lastname, $position, $pid);
echo json_encode($response);
?>

