<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$firstname = $_REQUEST['firstname'];
$lastname = $_REQUEST['lastname'];
$phone = $_REQUEST['phone'];
$email = $_REQUEST['email'];
$patronid = $_REQUEST['patronid'];

$response = addLibPatron($firstname, $lastname, $phone, $email, $patronid);
echo json_encode($response);
?>

