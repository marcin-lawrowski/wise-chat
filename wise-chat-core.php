<?php
/*
	Plugin Name: Wise Chat with AI
	Version: 3.4
	Plugin URI: https://kainex.pl/projects/wp-plugins/wise-chat
	Description: AI-powered, fully-featured chat plugin for WordPress. Supports AI integration, multiple channels, private messages, multisite installation, bad words filtering, themes, appearance settings, avatars, filters, bans and more.
	Author: Kainex
	Author URI: https://kainex.pl
	Text Domain: wise-chat
*/

use Kainex\WiseChat\Admin\Endpoints\AIBotsEndpoint;
use Kainex\WiseChat\ChatWidget;
use Kainex\WiseChat\Container;
use Kainex\WiseChat\Endpoints\AuthEndpoint;
use Kainex\WiseChat\Endpoints\CommandsEndpoint;
use Kainex\WiseChat\Endpoints\MaintenanceEndpoint;
use Kainex\WiseChat\Endpoints\MessageEndpoint;
use Kainex\WiseChat\Endpoints\MessagesEndpoint;
use Kainex\WiseChat\Endpoints\UserCommandEndpoint;
use Kainex\WiseChat\Installer;
use Kainex\WiseChat\Integrations\Elementor\WiseChatElementor;
use Kainex\WiseChat\Integrations\WordPress\Blocks;
use Kainex\WiseChat\Loader;
use Kainex\WiseChat\Options;
use Kainex\WiseChat\Services\ImagesService;
use Kainex\WiseChat\Services\User\UserService;
use Kainex\WiseChat\Settings;
use Kainex\WiseChat\StatsShortcode;
use Kainex\WiseChat\WiseChat;

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

define('WISE_CHAT_VERSION', '3.4');
define('WISE_CHAT_VERSION_AI', 1);
define('WISE_CHAT_ROOT', plugin_dir_path(__FILE__));
define('WISE_CHAT_NAME', 'Wise Chat');
define('WISE_CHAT_SLUG', strtolower(str_replace(' ', '_', WISE_CHAT_NAME)));

// class loader:
require_once(dirname(__FILE__).'/src/Loader.php');
Loader::install();

// DI container:
$container = Container::getInstance();

/** @var Options $options */
$options = $container->get(Options::class);

add_action('wp_enqueue_scripts', [$container->get(WiseChat::class), 'enqueueResources']);

function wise_chat_load_plugin_textdomain() {
	load_plugin_textdomain('wise-chat', false, basename(dirname(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'wise_chat_load_plugin_textdomain');

if ($options->isOptionEnabled('enabled_debug')) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

// store path for usage in engines:
Options::storeEngineConfig();

if (is_admin()) {
	Installer::setup(__FILE__);

    /** @var Settings $settings */
	$settings = $container->get(Settings::class);
    // initialize plugin settings page:
	$settings->initialize();

	add_action('admin_enqueue_scripts', function() use ($container) {
		wp_enqueue_media();
		/** @var WiseChat $wiseChat */
		$wiseChat = $container->get(WiseChat::class);
		$wiseChat->enqueueResources();
		wp_localize_script('wise-chat', '_wiseChatData', array('siteUrl' => get_site_url()));
	});
}

// register action that detects when WordPress user logs in / logs out:
function wise_chat_after_setup_theme_action() {
    /** @var UserService $userService */
	$userService = Container::getInstance()->get(UserService::class);
	$userService->switchUser();
}
add_action('wp_loaded', 'wise_chat_after_setup_theme_action');

// register chat shortcode:
function wise_chat_shortcode($atts) {
	/** @var WiseChat $wiseChat */
	$wiseChat = Container::getInstance()->get(WiseChat::class);
	return $wiseChat->getRenderedShortcode($atts);
}
add_shortcode('wise-chat', 'wise_chat_shortcode');

// register chat channel stats shortcode:
function wise_chat_channel_stats_shortcode($atts) {
	/** @var StatsShortcode $wiseChatStatsShortcode */
	$wiseChatStatsShortcode = Container::getInstance()->get(StatsShortcode::class);
	return $wiseChatStatsShortcode->getRenderedChannelStatsShortcode($atts);
}
add_shortcode('wise-chat-channel-stats', 'wise_chat_channel_stats_shortcode');

// chat function:
function wise_chat($channel = null) {
	/** @var WiseChat $wiseChat */
	$wiseChat = Container::getInstance()->get(WiseChat::class);
	echo $wiseChat->getRenderedChat(!is_array($channel) ? array($channel) : $channel);
}

// register chat widget:
function wise_chat_widget() {
	/** @var ChatWidget $widget */
	$widget = Container::getInstance()->get(ChatWidget::class);
	register_widget($widget);
}
add_action('widgets_init', 'wise_chat_widget');

add_action('init', array(Container::getInstance()->get(Blocks::class), 'register'));

// register action that auto-removes images generate by the chat (the additional thumbnail):
function wise_chat_action_delete_attachment($attachmentId) {
	/** @var ImagesService $wiseChatImagesService */
	$wiseChatImagesService = Container::getInstance()->get(ImagesService::class);
	$wiseChatImagesService->removeRelatedImages($attachmentId);
}
add_action('delete_attachment', 'wise_chat_action_delete_attachment');

// Endpoints fo AJAX requests:
function wise_chat_endpoint_messages() {
	/** @var MessagesEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(MessagesEndpoint::class);
	$wiseChatEndpoints->messagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');
add_action("wp_ajax_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');

function wise_chat_endpoint_past_messages() {
	/** @var MessagesEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(MessagesEndpoint::class);
	$wiseChatEndpoints->pastMessagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_past_messages_endpoint", 'wise_chat_endpoint_past_messages');
add_action("wp_ajax_wise_chat_past_messages_endpoint", 'wise_chat_endpoint_past_messages');

function wise_chat_endpoint_message() {
	/** @var MessageEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(MessageEndpoint::class);
	$wiseChatEndpoints->messageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_message_endpoint", 'wise_chat_endpoint_message');
add_action("wp_ajax_wise_chat_message_endpoint", 'wise_chat_endpoint_message');

function wise_chat_endpoint_get_message() {
	/** @var MessageEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(MessageEndpoint::class);
	$wiseChatEndpoints->getMessageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_get_message_endpoint", 'wise_chat_endpoint_get_message');
add_action("wp_ajax_wise_chat_get_message_endpoint", 'wise_chat_endpoint_get_message');

function wise_chat_endpoint_maintenance() {
	/** @var MaintenanceEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(MaintenanceEndpoint::class);
	$endpoint->maintenanceEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_maintenance_endpoint", 'wise_chat_endpoint_maintenance');
add_action("wp_ajax_wise_chat_maintenance_endpoint", 'wise_chat_endpoint_maintenance');

function wise_chat_endpoint_prepare_image() {
	/** @var UserCommandEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(UserCommandEndpoint::class);
	$endpoint->prepareImageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');
add_action("wp_ajax_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');

function wise_chat_endpoint_user_command() {
	/** @var CommandsEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(CommandsEndpoint::class);
	$endpoint->handle();
}
add_action("wp_ajax_nopriv_wise_chat_user_command_endpoint", 'wise_chat_endpoint_user_command');
add_action("wp_ajax_wise_chat_user_command_endpoint", 'wise_chat_endpoint_user_command');

function wise_chat_endpoint_auth() {
	/** @var AuthEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(AuthEndpoint::class);
	$endpoint->authEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_auth_endpoint", 'wise_chat_endpoint_auth');
add_action("wp_ajax_wise_chat_auth_endpoint", 'wise_chat_endpoint_auth');

function wise_chat_admin_user_search() {
	/** @var Settings $wiseChatSettings */
	$wiseChatSettings = Container::getInstance()->get(Settings::class);
	$wiseChatSettings->userSearchEndpoint();
}
add_action("wp_ajax_wise_chat_admin_user_search", 'wise_chat_admin_user_search');

if (defined('WISE_CHAT_VERSION_AI')) {
	add_action("wp_ajax_wise_chat_admin_ai_bot_create", function () {
		/** @var AIBotsEndpoint $botsService */
		$botsService = Container::getInstance()->get(AIBotsEndpoint::class);
		$botsService->createBot();
	});
	add_action("wp_ajax_wise_chat_admin_ai_bot_delete", function () {
		/** @var AIBotsEndpoint $botsService */
		$botsService = Container::getInstance()->get(AIBotsEndpoint::class);
		$botsService->deleteBot();
	});
	add_action("wp_ajax_wise_chat_admin_ai_bot_save", function () {
		/** @var AIBotsEndpoint $botsService */
		$botsService = Container::getInstance()->get(AIBotsEndpoint::class);
		$botsService->saveBot();
	});
}

function wise_chat_profile_update($userId, $oldUserData) {
	/** @var UserService $wiseChatUserService */
	$wiseChatUserService = Container::getInstance()->get(UserService::class);
	$wiseChatUserService->refreshUserBasedOnWordPressUser($userId);
}
add_action("profile_update", 'wise_chat_profile_update', 10, 2);

function wise_chat_elementor($widgetsManager) {
	/** @var WiseChatElementor $wiseChatElementor */
	$wiseChatElementor = Container::getInstance()->get(WiseChatElementor::class);
	$wiseChatElementor->register($widgetsManager);
}
add_action('elementor/widgets/register', 'wise_chat_elementor');