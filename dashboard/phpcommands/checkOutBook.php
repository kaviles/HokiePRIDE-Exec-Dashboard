<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$libid = $_REQUEST['libid'];
$patronid = $_REQUEST['patronid'];

$response = checkOutBook($libid, $patronid);
echo json_encode($response);
?>

