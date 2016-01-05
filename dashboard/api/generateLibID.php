<?php

include_once('../utility.php');

function handleRequestData($requestData) {
	return '{"responseCode":"1","message":"Successfully generated Library ID","libid":"'.generateLibID().'"}';
}

?>