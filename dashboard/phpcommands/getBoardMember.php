<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$pid = $_REQUEST['pid'];

$response = getBoardMember($pid);
echo json_encode($response);
?>

