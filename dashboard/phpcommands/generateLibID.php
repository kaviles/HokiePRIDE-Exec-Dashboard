<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$response = '{"responseCode":"1","message":"Successfully generated Library ID","libid":"'.generateLibID().'"}';
echo json_encode($response);
?>