<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$libid = $_REQUEST['libid'];

$response = retrieveDBBook($libid);
echo json_encode($response);
?>

