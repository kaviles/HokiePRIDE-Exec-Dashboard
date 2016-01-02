<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$holdingsRequest = array("holdingStatuses"=>$req_params['holdingStatuses'], "count"=>$req_params['count'],
	"offset"=>$req_params['offset']);

$response = retrieveHoldingsAdmin($holdingsRequest);
echo json_encode($response);
?>