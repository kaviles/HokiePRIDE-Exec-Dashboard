<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$libid = $_REQUEST['libid'];
$patronEmail = $_REQUEST['patronEmail'];

$response = checkOutBook($libid, $patronEmail);
echo json_encode($response);
?>

