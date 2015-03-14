<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$email = $_REQUEST['email'];

$response = retrieveLibPatron($email);
echo json_encode($response);
?>