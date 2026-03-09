<?php

	use Kainex\WiseChat\Container;
	use Kainex\WiseChat\Endpoints\MessagesEndpoint;
	use Kainex\WiseChat\Endpoints\UserCommandEndpoint;
	use Kainex\WiseChat\Loader;
	use Kainex\WiseChat\Services\ImagesService;
	use Kainex\WiseChat\Options;

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

	// class loader:
	require_once(dirname(__DIR__).'/Loader.php');
	Loader::install();

	require_once(dirname(__FILE__).'/wp_core.php');
	send_nosniff_header();

	define('WISE_CHAT_VERSION_AI', true);

	// DI container:
	$container = Container::getInstance();

	/** @var Options $options */
	$options = $container->get(Options::class);

	if ($options->isOptionEnabled('enabled_debug')) {
		error_reporting(E_ALL);
		ini_set("display_errors", 1);
	}

	global $wp_actions;
	$wp_actions[ 'plugins_loaded' ] = 1; // hack: to prevent warning from WP User Query

	// removing images downloaded by the chat:
	/** @var ImagesService $wiseChatImagesService */
	$wiseChatImagesService = $container->get(ImagesService::class);
	add_action('delete_attachment', array($wiseChatImagesService, 'removeRelatedImages'));
	
	$action = $_REQUEST['action'];
	if ($action === 'wise_chat_messages_endpoint') {
		/** @var MessagesEndpoint $endpoint */
		$endpoint = $container->get(MessagesEndpoint::class);
		$endpoint->messagesEndpoint();
	} else if ($action === 'wise_chat_prepare_image_endpoint') {
		/** @var UserCommandEndpoint $endpoint */
		$endpoint = $container->get(UserCommandEndpoint::class);
		$endpoint->prepareImageEndpoint();
	} else if ($action === 'check') {
		die('OK');
	} else {
		http_response_code(400);
        die(json_encode(['error' => 'Invalid action']));
	}