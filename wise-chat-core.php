<?php
/*
	Plugin Name: Wise Chat
	Version: 2.4.2
	Plugin URI: http://kaine.pl/projects/wp-plugins/wise-chat/wise-chat-donate
	Description: Fully-featured chat plugin for WordPress. It requires no server, supports multiple channels, bad words filtering, themes, appearance settings, filters, bans and more.
	Author: Marcin Ławrowski
	Author URI: http://kaine.pl
*/

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
}

// register action that detects when WordPress user logs in / logs out:
function wise_chat_after_setup_theme_action() {
    /** @var WiseChatUserService $userService */
	$userService = WiseChatContainer::get('services/user/WiseChatUserService');
	$userService->initMaintenance();
	$userService->switchUser();
}
add_action('after_setup_theme', 'wise_chat_after_setup_theme_action');

// register CSS file in HEAD section:
function wise_chat_register_common_css() {
	$pluginBaseURL = plugin_dir_url(__FILE__);
	wp_enqueue_style('wise_chat_core', $pluginBaseURL.'css/wise_chat.css');
}
add_action('wp_enqueue_scripts', 'wise_chat_register_common_css');

// register chat shortcode:
function wise_chat_shortcode($atts) {
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
	echo $wiseChat->getRenderedChat($channel);
	$wiseChat->registerResources();
}

// register chat widget:
function wise_chat_widget() {
	WiseChatContainer::get('WiseChatWidget');
	return register_widget("WiseChatWidget");
}
add_action('widgets_init', 'wise_chat_widget');

// register action that auto-removes images generate by the chat (the additional thumbnail):
function wise_chat_action_delete_attachment($attachmentId) {
	$wiseChatImagesService = WiseChatContainer::get('services/WiseChatImagesService');
	$wiseChatImagesService->removeRelatedImages($attachmentId);
}
add_action('delete_attachment', 'wise_chat_action_delete_attachment');


// Endpoints fo AJAX requests:
function wise_chat_endpoint_messages() {
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->messagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');
add_action("wp_ajax_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');

function wise_chat_endpoint_message() {
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->messageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_message_endpoint", 'wise_chat_endpoint_message');
add_action("wp_ajax_wise_chat_message_endpoint", 'wise_chat_endpoint_message');

function wise_chat_endpoint_message_delete() {
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->messageDeleteEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_delete_message_endpoint", 'wise_chat_endpoint_message_delete');
add_action("wp_ajax_wise_chat_delete_message_endpoint", 'wise_chat_endpoint_message_delete');

function wise_chat_endpoint_user_ban() {
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->userBanEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_user_ban_endpoint", 'wise_chat_endpoint_user_ban');
add_action("wp_ajax_wise_chat_user_ban_endpoint", 'wise_chat_endpoint_user_ban');

function wise_chat_endpoint_maintenance() {
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->maintenanceEndpoint();
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
	$wiseChatEndpoints = WiseChatContainer::get('endpoints/WiseChatEndpoints');
	$wiseChatEndpoints->prepareImageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');
add_action("wp_ajax_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');
