<?php

namespace Kainex\WiseChat;

use Exception;
use Kainex\WiseChat\Services\MessagesService;

/**
 * WiseChat installer.
 *
 * @author Kainex <contact@kaine.pl>
 */
class Installer {

	const GOLD_ENGINE_MU_PLUGIN = '0-wise-chat-engine.php';
	private static $forceDefaultOptions = [];

	public static function getUsersTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_users';
	}

	public static function getMessagesTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_messages';
	}

	public static function getReactionsTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_reactions';
	}

	public static function getReactionsLogTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_reactions_log';
	}
	
	public static function getUserMutesTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_bans';
	}

	public static function getOpenAiRunsTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_open_ai_runs';
	}

	public static function getBansTable() {
		global $wpdb;
		return $wpdb->prefix.'wise_chat_kicks';
	}
	
	public static function getActionsTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_actions';
	}
	
	public static function getChannelUsersTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_channel_users';
	}
	
	public static function getChannelsTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_channels';
	}

	public static function getChannelMembersTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_channel_members';
	}

	public static function getUserChannelsTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_user_channels';
	}

	public static function getOpenUserChannelsTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_open_user_channels';
	}

	public static function getUserFeedTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_user_feed';
	}

	public static function getPendingChatsTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_pending_chats';
	}

	public static function getSentNotificationsTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_sent_notifications';
	}

	/**
	 * Plugin's activation action. Creates database structure (if does not exist), upgrades database structure and
	 * initializes options. Supports WordPress multisite.
	 *
	 * @param boolean $networkWide True if it is a network activation - if so, run the activation function for each blog id
	 */
	public static function activate($networkWide) {
		global $wpdb;

		if (self::registerEngine()) {
			self::$forceDefaultOptions = ['ajax_engine' => 'gold'];
		}

		$role = get_role('administrator');
		$role->add_cap(Settings::CAPABILITY);

		if (function_exists('is_multisite') && is_multisite()) {
			if ($networkWide) {
				$oldBlogID = $wpdb->blogid;
				$blogIDs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
				foreach ($blogIDs as $blogID) {
					switch_to_blog($blogID);
					self::doActivation();
				}
				switch_to_blog($oldBlogID);
				return;
			}
		}
		self::doActivation();
	}

	/**
	 * Executed when admin creates a site in mutisite installation.
	 *
	 * @param integer $blogID
	 * @param integer $userID
	 * @param string $domain
	 * @param string $path
	 * @param string $siteID
	 * @param mixed $meta
	 */
	public static function newBlog($blogID, $userID, $domain, $path, $siteID, $meta) {
		global $wpdb;

		if (is_plugin_active_for_network(WISE_CHAT_SLUG.'/wise-chat-core.php')) {
			$oldBlogID = $wpdb->blogid;
			switch_to_blog($blogID);
			self::doActivation();
			switch_to_blog($oldBlogID);
		}
	}

	/**
	 * Executed when admin deletes a site in mutisite installation.
	 *
	 * @param int $blogID Blog ID
	 * @param bool $drop True if blog's table should be dropped. Default is false.
	 */
	public static function deleteBlog($blogID, $drop) {
		global $wpdb;

		$oldBlogID = $wpdb->blogid;
		switch_to_blog($blogID);
		self::doUninstall('deleteblog_'.$blogID);
		switch_to_blog($oldBlogID);
	}

	private static function doActivation() {
		global $wpdb, $user_level, $sac_admin_user_level;
		
		if ($user_level < $sac_admin_user_level) {
			return;
		}

		$charsetCollate = $wpdb->get_charset_collate();

		$tableName = self::getUsersTable();
		$sql = "CREATE TABLE ".$tableName." (
				id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				wp_id bigint(11),
				name text NOT NULL,
				session_id text NOT NULL,
				external_type text,
				external_id text,
				avatar_url text,
				profile_url text,
				ip text,
				created bigint(11) DEFAULT '0' NOT NULL,
				data text
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$tableName = self::getMessagesTable();
		$sql = "CREATE TABLE ".$tableName." (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				hidden boolean default 0,
				time bigint(11) DEFAULT '0' NOT NULL, 
				admin boolean not null default 0,
				user_id bigint(11),
				chat_user_id bigint(11),
				reply_to_message_id mediumint(7),
				channel_id mediumint(7),
				text text NOT NULL, 
				ip text NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		// drop column: channel_user_id

		$tableName = self::getReactionsTable();
		$sql = "CREATE TABLE ".$tableName." (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				reaction_1 bigint(11) DEFAULT '0' NOT NULL,
				reaction_2 bigint(11) DEFAULT '0' NOT NULL,
				reaction_3 bigint(11) DEFAULT '0' NOT NULL,
				reaction_4 bigint(11) DEFAULT '0' NOT NULL,
				reaction_5 bigint(11) DEFAULT '0' NOT NULL,
				reaction_6 bigint(11) DEFAULT '0' NOT NULL,
				reaction_7 bigint(11) DEFAULT '0' NOT NULL,
				message_id mediumint(7),
				updated bigint(11) DEFAULT '0' NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getReactionsLogTable();
		$sql = "CREATE TABLE ".$tableName." (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				message_id mediumint(7),
				reaction_id mediumint(7),
				time bigint(11) DEFAULT '0' NOT NULL,
				user_id bigint(11)
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$tableName = self::getUserMutesTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
				time bigint(11) DEFAULT '0' NOT NULL,
				created bigint(11) DEFAULT '0' NOT NULL,
				user_id bigint(11),
				ip text NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getBansTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				created bigint(11) DEFAULT '0' NOT NULL,
				user_id bigint(11),
				ip text NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$tableName = self::getActionsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				time bigint(11) DEFAULT '0' NOT NULL,
				user_id bigint(11),
				command text NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		// drop column: user
		
		$tableName = self::getChannelUsersTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				channel_id bigint(11),
				user_id bigint(11),
				active boolean not null default 1,
				last_activity_time bigint(11) DEFAULT '0' NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		// drop column: user, channel, session_id, ip
		
		$tableName = self::getChannelsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name text NOT NULL,
				type int NOT NULL DEFAULT 1,
				password text,
				configuration JSON
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getChannelMembersTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				channel_id bigint(11) NOT NULL,
				user_id bigint(11) NOT NULL,
				confirmed boolean not null default 0,
				type int NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getUserChannelsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				channel_id bigint(11) NOT NULL,
				user_id bigint(11) NOT NULL,
				sort int
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getOpenUserChannelsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				channel_id bigint(11) NOT NULL,
				user_id bigint(11) NOT NULL,
				sort int
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getUserFeedTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				type text NOT NULL,
				user_id bigint(11) NOT NULL,
				target_id bigint(11),
				seen boolean not null default 0,
				data JSON,
				created DATETIME NOT NULL DEFAULT NOW()
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getPendingChatsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				channel_id bigint(11),
				user_id bigint(11),
				recipient_id bigint(11),
				message_id bigint(11),
				time bigint(11) DEFAULT '0' NOT NULL,
				checked boolean not null default 0
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getSentNotificationsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				channel_id bigint(11),
				user_id bigint(11),
				notification_id text NOT NULL,
				sent_time bigint(11) DEFAULT '0' NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getOpenAiRunsTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				run_id varchar(255),
				bot_user_id bigint(11),
				user_id bigint(11),
				message_id bigint(11),
				created DATETIME NOT NULL DEFAULT NOW(),
				thread_id text,
				status text
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// set default options after installation:
		/** @var Settings $settings */
		$settings = Container::getInstance()->get(Settings::class);
		$settings->setDefaultSettings(self::$forceDefaultOptions);

		self::upgradeMessagesTableCharset();
		self::upgradeMessagesInPublicChannels();
	}

	private static function upgradeMessagesTableCharset()
	{
		global $wpdb;

		$convert = false;
		if (method_exists($wpdb, 'get_col_charset')) {
			$convert = $wpdb->get_col_charset(self::getMessagesTable(), 'text') === 'utf8';
		} else {
			$results = $wpdb->get_results(sprintf("SHOW CREATE TABLE %s;", self::getMessagesTable()), ARRAY_N);
			if (count($results) > 0 && count($results[0]) > 0) {
				$convert = preg_match("/CHARSET=utf8 /m", $results[0][1]) > 0;
			}
		}

		if ($convert && $wpdb->charset !== 'utf8') {
			$charsetCollate = '';

			if (!empty($wpdb->charset)) {
				$charsetCollate = "CHARACTER SET ".$wpdb->charset;
			}
			if (!empty($wpdb->collate)) {
				$charsetCollate .= " COLLATE ".$wpdb->collate;
			}

			if ($charsetCollate) {
				$wpdb->query(sprintf('ALTER TABLE %s CONVERT TO %s;', self::getMessagesTable(), $charsetCollate));
			}
		}
	}

	private static function upgradeMessagesInPublicChannels() {
		global $wpdb;
		$channelsTable = self::getChannelsTable();
		$messagesTable = self::getMessagesTable();
		$membersTable = self::getChannelMembersTable();

		// move public messages to channels:
		if (self::columnExists($messagesTable, 'channel') && self::columnExists($messagesTable, 'chat_recipient_id')) {
			$wpdb->query($wpdb->prepare("UPDATE %i SET channel_id = (SELECT ch.id FROM %i ch WHERE ch.name = channel) WHERE channel_id is null AND channel != '' AND chat_recipient_id IS NULL;", $messagesTable, $channelsTable));
			$wpdb->query($wpdb->prepare("UPDATE %i SET channel = '' WHERE channel_id is not null AND channel != '' AND chat_recipient_id IS NULL;", $messagesTable));
		}

		// move private messages to channels:
		$rows = self::columnExists($messagesTable, 'chat_recipient_id') ? $wpdb->query(
			$wpdb->prepare("insert into %i (name, type) SELECT DISTINCT concat(LEAST(chat_user_id, chat_recipient_id), '_', GREATEST(chat_user_id, chat_recipient_id)) as name, 3 as type FROM %i WHERE chat_recipient_id is not null ORDER BY name;",
			$channelsTable, $messagesTable
		)) : [];
		if ($rows > 0) {
			$sql = $wpdb->prepare('SELECT * FROM %i WHERE `type` = 3;', $channelsTable);
			$results = $wpdb->get_results($sql);
			foreach ($results as $channelRow) {
				$nameSpl = explode('_', $channelRow->name);

				$wpdb->query(
					$wpdb->prepare("insert into %i (channel_id, user_id, confirmed, type) values (%d, %d, 1, 1);",
						$membersTable, $channelRow->id, $nameSpl[0]
					));
				$wpdb->query(
					$wpdb->prepare("insert into %i (channel_id, user_id, confirmed, type) values (%d, %d, 1, 1);",
						$membersTable, $channelRow->id, $nameSpl[1]
					));
			}

			if (self::columnExists($messagesTable, 'channel') && self::columnExists($messagesTable, 'chat_recipient_id')) {
				$wpdb->query(
					$wpdb->prepare("UPDATE %i m SET channel_id = (SELECT ch.id FROM %i ch WHERE ch.type=3 and ch.name = concat(LEAST(m.chat_user_id, m.chat_recipient_id), '_', GREATEST(m.chat_user_id, m.chat_recipient_id))) WHERE m.chat_recipient_id > 0;",
						$messagesTable, $channelsTable
					));

				$wpdb->query($wpdb->prepare("UPDATE %i SET channel = '', chat_recipient_id = null WHERE chat_recipient_id IS NOT NULL;", $messagesTable));
			}
		}
	}

	private static function columnExists($table_name, $col_name): bool {
		global $wpdb;

		$results = $wpdb->get_results("DESC $table_name");

		foreach ($results as $row) {
			if ($row->Field === $col_name) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Plugin's deactivation action.
	 */
	public static function deactivate() {
		global $wpdb, $user_level, $sac_admin_user_level;

		$role = get_role('administrator');
		$role->remove_cap(Settings::CAPABILITY);
		
		if ($user_level < $sac_admin_user_level) {
			return;
		}
	}

	/**
	 * Plugin's uninstall action. Deletes all database tables and plugin's options.
	 * Supports WordPress multisite.
	 */
	public static function uninstall() {
		global $wpdb;

		if (function_exists('is_multisite') && is_multisite()) {
			$oldBlogID = $wpdb->blogid;
			$blogIDs = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blogIDs as $blogID) {
				switch_to_blog($blogID);
				self::doUninstall();
			}
			switch_to_blog($oldBlogID);
			return;
		}
		self::doUninstall();
	}

	private static function doUninstall($refererCheck = null) {
		if (!current_user_can('activate_plugins')) {
			return;
		}
		if ($refererCheck !== null) {
			check_admin_referer($refererCheck);
		}
        
        global $wpdb;
		
		// remove all messages and related images:
        /** @var MessagesService $messagesService */
		$messagesService = Container::getInstance()->get(MessagesService::class);
        $messagesService->deleteAll();
		
		$tableName = self::getMessagesTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getUserMutesTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getBansTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getActionsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getChannelUsersTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getChannelMembersTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getUserChannelsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getOpenUserChannelsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getUserFeedTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getChannelsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getUsersTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getPendingChatsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getSentNotificationsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getOpenAiRunsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getReactionsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getReactionsLogTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		/** @var Options $options */
		$options = Container::getInstance()->get(Options::class);
		$options->dropAllOptions();

		self::unregisterEngine();
	}

	/**
	 * Registers MU plugin to support Gold engine.
	 *
	 * @return bool
	 */
	public static function registerEngine() {
		$engineContent = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'Setup'.DIRECTORY_SEPARATOR.self::GOLD_ENGINE_MU_PLUGIN);
		$engineContent = str_replace(['{{PLUGIN_DIR}}', '{{PLUGIN_NAME}}'], [WISE_CHAT_ROOT, WISE_CHAT_NAME], $engineContent);

		$mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
		$mustUsePluginPath = $mustUsePluginDir.'/'.self::GOLD_ENGINE_MU_PLUGIN;

		try {
			if (file_exists($mustUsePluginPath) && md5($engineContent) === md5_file($mustUsePluginPath)) {
				return true;
			}

			if (!is_dir($mustUsePluginDir)) {
				$dirMade = @mkdir($mustUsePluginDir);
				if (!$dirMade) {
					$error = error_get_last();
					error_log(sprintf('Unable to create engine directory: %s', $error['message']));
					return false;
				}
			}

			if (!is_writable($mustUsePluginDir)) {
				error_log('MU-plugin directory is not writable.');
				return false;
			}

			$loaderWritten = @file_put_contents($mustUsePluginPath, $engineContent);
			if (!$loaderWritten) {
				$error = error_get_last();
				error_log(sprintf('Unable to install the engine: %s', $error['message']));
				return false;
			}
		} catch (Exception $e) {
			error_log('Unable to install the engine: '.$e->getMessage());
		}

		return true;
    }

	/**
	 * Removes the engine.
	 */
    public static function unregisterEngine() {
		try {
			$mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
			$mustUsePluginPath = $mustUsePluginDir.'/'.self::GOLD_ENGINE_MU_PLUGIN;

			if (!file_exists($mustUsePluginPath)) {
				return;
			}

			$removed = @unlink($mustUsePluginPath);

			if (!$removed) {
				$error = error_get_last();
				error_log(sprintf('Unable to remove the engine: %s', $error['message']));
			}
		} catch (\Exception $e) {
			error_log('Unable to delete the engine: '.$e->getMessage());
		}
	}

	/**
	 * Installs all plugin activation hooks.
	 *
	 * @param string $pluginFile
	 */
	public static function setup(string $pluginFile) {
		register_activation_hook($pluginFile, [self::class, 'activate']);
		register_deactivation_hook($pluginFile, [self::class, 'deactivate']);
		register_uninstall_hook($pluginFile, [self::class, 'uninstall']);
		add_action('wpmu_new_blog', [self::class, 'newBlog'], 10, 6);
		add_action('delete_blog', [self::class, 'deleteBlog'], 10, 6);
	}
}