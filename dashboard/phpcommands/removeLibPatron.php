<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$email = $_REQUEST['email'];
$reason = $_REQUEST['reason'];

$response = removeLibPatron($email, $reason);
echo json_encode($response);
?>

