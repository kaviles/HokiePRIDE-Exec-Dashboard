<?php
include_once('dashcommands.php');

header('Content-Type: application/json');

$title = $_REQUEST['title'];
$author = $_REQUEST['author'];
$genre = $_REQUEST['genre'];
$publisher = $_REQUEST['publisher'];
$isbn = $_REQUEST['isbn'];
$loc = $_REQUEST['loc'];
$dcc = $_REQUEST['dcc'];
$tags = $_REQUEST['tags'];
$comms = $_REQUEST['comments'];


$response = addBook($title, $author, $genre, $publisher, $isbn, $loc, $dcc, $tags, $comms);
echo json_encode($response);
?>

