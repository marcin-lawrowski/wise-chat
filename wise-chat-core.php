<?php
/*
	Plugin Name: Wise Chat
	Version: 3.1.1
	Plugin URI: https://kainex.pl/projects/wp-plugins/wise-chat
	Description: Fully-featured chat plugin for WordPress. It requires no server, supports multiple channels, bad words filtering, themes, appearance settings, filters, bans and more.
	Author: Kainex
	Author URI: https://kainex.pl
	Text Domain: wise-chat
*/

define('WISE_CHAT_VERSION', '3.1.1');

require_once(dirname(__FILE__).'/src/WiseChatContainer.php');
WiseChatContainer::load('WiseChatInstaller');
WiseChatContainer::load('WiseChatOptions');

if (WiseChatOptions::getInstance()->isOptionEnabled('enabled_debug', false)) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

if (is_admin()) {
	// installer:
	register_activation_hook(__FILE__, array('WiseChatInstaller', 'activate'));
	register_deactivation_hook(__FILE__, array('WiseChatInstaller', 'deactivate'));
	register_uninstall_hook(__FILE__, array('WiseChatInstaller', 'uninstall'));

    /** @var WiseChatSettings $settings */
	$settings = WiseChatContainer::get('WiseChatSettings');
    // initialize plugin settings page:
	$settings->initialize();

	add_action('admin_enqueue_scripts', function() {
		wp_enqueue_media();
	});
}

// register action that detects when WordPress user logs in / logs out:
function wise_chat_after_setup_theme_action() {
    /** @var WiseChatUserService $userService */
	$userService = WiseChatContainer::get('services/user/WiseChatUserService');
	$userService->switchUser();
}
add_action('after_setup_theme', 'wise_chat_after_setup_theme_action');

// register CSS file in HEAD section:
function wise_chat_register_common_css() {
	$pluginBaseURL = plugin_dir_url(__FILE__);
	wp_enqueue_style('wise_chat_libs', $pluginBaseURL.'assets/css/wise-chat-libs.min.css?v='.WISE_CHAT_VERSION);
	wp_enqueue_style('wise_chat_core', $pluginBaseURL.'assets/css/wise-chat.min.css?v='.WISE_CHAT_VERSION);
}
add_action('wp_enqueue_scripts', 'wise_chat_register_common_css');

// register chat shortcode:
function wise_chat_shortcode($atts) {
	/** @var WiseChat $wiseChat */
	$wiseChat = WiseChatContainer::get('WiseChat');
	$html = $wiseChat->getRenderedShortcode($atts);
	$wiseChat->registerResources();
    return $html;
}
add_shortcode('wise-chat', 'wise_chat_shortcode');

// register chat channel stats shortcode:
function wise_chat_channel_stats_shortcode($atts) {
	$wiseChatStatsShortcode = WiseChatContainer::get('WiseChatStatsShortcode');
	return $wiseChatStatsShortcode->getRenderedChannelStatsShortcode($atts);
}
add_shortcode('wise-chat-channel-stats', 'wise_chat_channel_stats_shortcode');

// chat function:
function wise_chat($channel = null) {
	$wiseChat = WiseChatContainer::get('WiseChat');
	echo $wiseChat->getRenderedChat(!is_array($channel) ? array($channel) : $channel);
	$wiseChat->registerResources();
}

// register chat widget:
function wise_chat_widget() {
	WiseChatContainer::get('WiseChatWidget');
	register_widget("WiseChatWidget");
}
add_action('widgets_init', 'wise_chat_widget');

// register action that auto-removes images generate by the chat (the additional thumbnail):
function wise_chat_action_delete_attachment($attachmentId) {
	/** @var WiseChatImagesService $wiseChatImagesService */
	$wiseChatImagesService = WiseChatContainer::get('services/WiseChatImagesService');
	$wiseChatImagesService->removeRelatedImages($attachmentId);
}
add_action('delete_attachment', 'wise_chat_action_delete_attachment');

// Endpoints fo AJAX requests:
function wise_chat_endpoint_messages() {
	/** @var WiseChatMessagesEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatMessagesEndpoint');
	$wiseChatEndpoints->messagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');
add_action("wp_ajax_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');

function wise_chat_endpoint_past_messages() {
	/** @var WiseChatMessagesEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatMessagesEndpoint');
	$wiseChatEndpoints->pastMessagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_past_messages_endpoint", 'wise_chat_endpoint_past_messages');
add_action("wp_ajax_wise_chat_past_messages_endpoint", 'wise_chat_endpoint_past_messages');

function wise_chat_endpoint_message() {
	/** @var WiseChatMessageEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatMessageEndpoint');
	$wiseChatEndpoints->messageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_message_endpoint", 'wise_chat_endpoint_message');
add_action("wp_ajax_wise_chat_message_endpoint", 'wise_chat_endpoint_message');

function wise_chat_endpoint_maintenance() {
	/** @var WiseChatMaintenanceEndpoint $endpoint */
	$endpoint = WiseChatContainer::get('endpoints/WiseChatMaintenanceEndpoint');
	$endpoint->maintenanceEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_maintenance_endpoint", 'wise_chat_endpoint_maintenance');
add_action("wp_ajax_wise_chat_maintenance_endpoint", 'wise_chat_endpoint_maintenance');

function wise_chat_endpoint_settings() {
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->settingsEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_settings_endpoint", 'wise_chat_endpoint_settings');
add_action("wp_ajax_wise_chat_settings_endpoint", 'wise_chat_endpoint_settings');

function wise_chat_endpoint_prepare_image() {
	/** @var WiseChatUserCommandEndpoint $endpoint */
	$endpoint = WiseChatContainer::get('endpoints/WiseChatUserCommandEndpoint');
	$endpoint->prepareImageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');
add_action("wp_ajax_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');

function wise_chat_endpoint_user_command() {
	/** @var WiseChatUserCommandEndpoint $endpoint */
	$endpoint = WiseChatContainer::get('endpoints/WiseChatUserCommandEndpoint');
	$endpoint->userCommandEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_user_command_endpoint", 'wise_chat_endpoint_user_command');
add_action("wp_ajax_wise_chat_user_command_endpoint", 'wise_chat_endpoint_user_command');

function wise_chat_endpoint_auth() {
	/** @var WiseChatAuthEndpoint $endpoint */
	$endpoint = WiseChatContainer::get('endpoints/WiseChatAuthEndpoint');
	$endpoint->authEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_auth_endpoint", 'wise_chat_endpoint_auth');
add_action("wp_ajax_wise_chat_auth_endpoint", 'wise_chat_endpoint_auth');

function wise_chat_profile_update($userId, $oldUserData) {
	/** @var WiseChatUserService $wiseChatUserService */
	$wiseChatUserService = WiseChatContainer::get('services/user/WiseChatUserService');
	$wiseChatUserService->onWpUserProfileUpdate($userId, $oldUserData);
}
add_action("profile_update", 'wise_chat_profile_update', 10, 2);

function wise_chat_load_plugin_textdomain() {
	load_plugin_textdomain('wise-chat', false, basename(dirname(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'wise_chat_load_plugin_textdomain');

function wise_chat_elementor($widgetsManager) {
	/** @var WiseChatElementor $wiseChatElementor */
	$wiseChatElementor = WiseChatContainer::get('integrations/elementor/WiseChatElementor');
	$wiseChatElementor->register($widgetsManager);
}
add_action('elementor/widgets/register', 'wise_chat_elementor');