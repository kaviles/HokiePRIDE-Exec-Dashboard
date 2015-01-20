<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$pid = $_REQUEST['pid'];

$response = deleteBoardMember($pid);
echo json_encode($response);
?>

