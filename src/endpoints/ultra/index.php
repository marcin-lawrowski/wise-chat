<?php

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    die(json_encode(['error' => 'Only AJAX requests allowed']));
}

// make sure it is used only for "wise_chat_messages_endpoint" action:
if (!isset($_REQUEST['action']) || !in_array($_REQUEST['action'], array('wise_chat_messages_endpoint', 'check'))) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid action requested']));
}

// check required parameters
if (!isset($_REQUEST['channelIds']) || !isset($_REQUEST['lastId'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing required parameters']));
}
$channelIds = array_map('intval', array_filter($_REQUEST['channelIds']));
$lastTimeGmt = $_REQUEST['lastCheckTime'];
$fromActionId = intval($_REQUEST['fromActionId']);
$lastTime = strtotime($lastTimeGmt);
$lastTime = $lastTime === false ? 0 : $lastTime - 5;

// read wp-config.php file and parse its constants and variables:
require_once('wp-config-parser.php');
if (!loadConfigFile()) {
	http_response_code(400);
    die(json_encode(['error' => 'Could not load standard WordPress config file: wp-config.php: '.getLastError()]));
}
$constants = getConfigConstants();
$variables = getConfigVariables();
clearConfigFileData();

// multisite support:
if (isset($_REQUEST['blogId'])) {
    $blogId = intval($_REQUEST['blogId']);
    if ($blogId > 1) {
        $variables['table_prefix'] .= $blogId.'_';
    }
}
if (!isset($variables['table_prefix'])) {
	http_response_code(400);
    die(json_encode(['error' => 'Missing config variable: table_prefix']));
}

// set headers:
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Content-Type: application/json');

// show errors when debug mode is on:
if (isset($constants['WP_DEBUG']) && $constants['WP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
}

// check constants:
$checkConstants = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'];
foreach ($checkConstants as $checkConstant) {
	if (!isset($constants[$checkConstant])) {
		http_response_code(400);
        die(json_encode(['error' => 'Missing config constant: '.$checkConstant]));
	}
}

// connect to db:
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
	$dbWC = new mysqli($constants['DB_HOST'], $constants['DB_USER'], $constants['DB_PASSWORD'], $constants['DB_NAME']);

	// get a message:
	$channelsTable = $variables['table_prefix'].'wise_chat_channels';
	$messagesTable = $variables['table_prefix'].'wise_chat_messages';
	$sql = sprintf("SELECT id FROM %s WHERE (`channel` IN (SELECT `name` FROM %s WHERE `id` IN (%s)) OR `channel` = '__private') AND `time` > %d LIMIT 1;", $messagesTable, $channelsTable, implode(', ', $channelIds), $lastTime);
	$result = $dbWC->query($sql);
	$rowsNum = $result->num_rows;
	$result->free();

	$actionsNum = 0;
	if ($rowsNum === 0) {
		// check for any new action
		$actionsTable = $variables['table_prefix'] . 'wise_chat_actions';
		$sql = sprintf("SELECT id FROM %s WHERE `id` > %d LIMIT 1;", $actionsTable, $fromActionId);
		$result = $dbWC->query($sql);
		$actionsNum = $result->num_rows;
		$result->free();
	}

	$dbWC->close();
} catch (mysqli_sql_exception $e) {
	http_response_code(400);
    $data = [
        'error' => 'Database connection error: '.$e->getMessage()
    ];
    die(json_encode($data));
}

if ($rowsNum === 0 && $actionsNum === 0) {
	if ($_REQUEST['action'] === 'check') {
		die('OK');
	}

    $response = array(
        'ultra' => $lastTime,
        'nowTime' => gmdate('c', time()),
        'result' => array()
    );
    die(json_encode($response));
} else {
    // forward the request:
    require_once('../index.php');
}