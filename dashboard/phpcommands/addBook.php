<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$bookData = array("title"=>$_REQUEST['title'], "author"=>$_REQUEST['author'],
	"pub"=>$_REQUEST['pub'], "year"=>$_REQUEST['year'], "isbn13"=>$_REQUEST['isbn13'],
	"loc"=>$_REQUEST['loc'], "dcc"=>$_REQUEST['dcc'], "tags"=>$_REQUEST['tags'],
	"covurl"=>$_REQUEST['covurl'], "comms"=>$_REQUEST['desc'], "libid"=>$_REQUEST['libid']);

$response = addBook($bookData);
echo json_encode($response);
?>

