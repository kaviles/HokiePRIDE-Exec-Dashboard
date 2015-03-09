<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$isbn13 = $_REQUEST['isbn13'];

$response = retrieveBookData($isbn13);
echo json_encode($response);
?>

