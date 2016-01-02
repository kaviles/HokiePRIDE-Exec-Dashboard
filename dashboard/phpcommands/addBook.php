<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$req_params = json_decode(implode("", $_GET), true);

$bookData = array("title"=>$req_params['title'], "author"=>$req_params['author'],
	"pub"=>$req_params['pub'], "year"=>$req_params['year'], "isbn13"=>$req_params['isbn13'],
	"loc"=>$req_params['loc'], "dcc"=>$req_params['dcc'], "tags"=>$req_params['tags'],
	"covurl"=>$req_params['covurl'], "comms"=>$req_params['desc'], "libid"=>$req_params['libid']);

$response = addBook($bookData);
echo json_encode($response);
?>

