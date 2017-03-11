<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	header('HTTP/1.0 404 Not Found');
	die('');
}

// make sure it is used only for "wise_chat_messages_endpoint" action:
if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'wise_chat_messages_endpoint') {
	header('HTTP/1.0 404 Not Found');
	die('');
}

// check required parameters
if (!isset($_REQUEST['channelId']) || !isset($_REQUEST['lastId'])) {
	header('HTTP/1.0 404 Not Found');
	die('');
}
$channelId = intval($_REQUEST['channelId']);
$lastId = intval($_REQUEST['lastId']);

// read wp-config.php file and parse its constants and variables:
require_once('wp-config-parser.php');
if (!loadConfigFile()) {
	header('HTTP/1.0 400 Bad Request', true, 400);
	$data = array(
		'error' => 'Could not load standard WordPress config file: wp-config.php'
	);
	die(json_encode($data));
}
$constants = getConfigConstants();
$variables = getConfigVariables();
clearConfigFileData();

// set headers:
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Content-Type: application/json');

// show errors when debug mode is on:
if (isset($constants['WP_DEBUG']) && $constants['WP_DEBUG'] === 'true') {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

// connect to db:
$dbWC = new mysqli($constants['DB_HOST'], $constants['DB_USER'], $constants['DB_PASSWORD'], $constants['DB_NAME']);
if ($dbWC->connect_errno) {
	header('HTTP/1.0 400 Bad Request', true, 400);
	$data = array(
		'error' => $dbWC->connect_error.', Error = '.$dbWC->connect_errno
	);
	die(json_encode($data));
}

// get a message:
$channelsTable = $variables['table_prefix'].'wise_chat_channels';
$messagesTable = $variables['table_prefix'].'wise_chat_messages';
$sql = sprintf("SELECT id FROM %s WHERE channel = (SELECT name FROM %s WHERE id = %d) AND id > %d LIMIT 1;", $messagesTable, $channelsTable, $channelId, $lastId);
if (!$result = $dbWC->query($sql)) {
	header('HTTP/1.0 400 Bad Request', true, 400);
	$data = array(
		'error' => $dbWC->error.', Error = '.$dbWC->errno
	);
	die(json_encode($data));
}
$rowsNum = $result->num_rows;
$result->free();
$dbWC->close();

if ($rowsNum === 0) {
	// return empty content:
	$response = array(
		'nowTime' => gmdate('c', time()),
		'result' => array()
	);
	echo json_encode($response);
	die('');
} else {
	// forward the request:
	require_once('../index.php');
}