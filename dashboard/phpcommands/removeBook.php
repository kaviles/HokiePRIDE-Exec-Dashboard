<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$libid = $_REQUEST['libid'];
$reason = $_REQUEST['reason'];

$response = removeBook($libid, $reason);
echo json_encode($response);
?>

