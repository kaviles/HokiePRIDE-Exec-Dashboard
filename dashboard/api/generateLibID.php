<?php

include_once(__DIR__.'/../utility.php');

function handleRequestData($requestData) {
	return '{"responseCode":"1","message":"Successfully generated Library ID","libid":"'.generateLibID().'"}';
}

?>