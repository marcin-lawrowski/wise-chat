<?php
	define('DOING_AJAX', true);
	define('SHORTINIT', true);
	
	if (!isset($_REQUEST['action'])) {
	    http_response_code(400);
        die(json_encode(['error' => 'No action specified']));
	}
	header('Content-Type: text/html');
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');

	ini_set('html_errors', 0);

	require_once(dirname(__DIR__).'/WiseChatContainer.php');
	WiseChatContainer::load('WiseChatInstaller');
	WiseChatContainer::load('WiseChatOptions');
	require_once(dirname(__FILE__).'/wp_core.php');

	send_nosniff_header();

	if (WiseChatOptions::getInstance()->isOptionEnabled('enabled_debug', false)) {
		error_reporting(E_ALL);
		ini_set("display_errors", 1);
	}

	// removing images downloaded by the chat:
	/** @var WiseChatImagesService $wiseChatImagesService */
	$wiseChatImagesService = WiseChatContainer::get('services/WiseChatImagesService');
	add_action('delete_attachment', array($wiseChatImagesService, 'removeRelatedImages'));
	
	$action = $_REQUEST['action'];
	if ($action === 'wise_chat_messages_endpoint') {
		/** @var WiseChatMessagesEndpoint $endpoint */
		$endpoint = WiseChatContainer::get('endpoints/WiseChatMessagesEndpoint');
		$endpoint->messagesEndpoint();
	} else if ($action === 'wise_chat_prepare_image_endpoint') {
		/** @var WiseChatUserCommandEndpoint $endpoint */
		$endpoint = WiseChatContainer::get('endpoints/WiseChatUserCommandEndpoint');
		$endpoint->prepareImageEndpoint();
	} else if ($action === 'check') {
		die('OK');
	} else {
		http_response_code(400);
        die(json_encode(['error' => 'Invalid action']));
	}