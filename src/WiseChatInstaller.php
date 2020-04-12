<?php

/**
 * WiseChat installer.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatInstaller {

	public static function getUsersTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_users';
	}

	public static function getMessagesTable() {
		global $wpdb;
		
		return $wpdb->prefix.'wise_chat_messages';
	}
	
	public static function getBansTable() {
		global $wpdb;

		return $wpdb->prefix.'wise_chat_bans';
	}

	public static function getKicksTable() {
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

	public static function activate() {
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
				ip text,
				created bigint(11) DEFAULT '0' NOT NULL,
				data text
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		$tableName = self::getMessagesTable();
		$sql = "CREATE TABLE ".$tableName." (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
				time bigint(11) DEFAULT '0' NOT NULL, 
				admin boolean not null default 0,
				user tinytext NOT NULL,
				user_id bigint(11),
				chat_user_id bigint(11),
				channel text NOT NULL, 
				text text NOT NULL, 
				ip text NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		// drop column: channel_user_id
		
		// remove legacy messages:
		$wpdb->get_results('DELETE FROM '.$tableName.' WHERE text = "__user_ping";');
		
		$tableName = self::getBansTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
				time bigint(11) DEFAULT '0' NOT NULL,
				created bigint(11) DEFAULT '0' NOT NULL,
				ip text NOT NULL
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		$tableName = self::getKicksTable();
		$sql = "CREATE TABLE " . $tableName . " (
				id mediumint(7) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				last_user_name text NOT NULL,
				created bigint(11) DEFAULT '0' NOT NULL,
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
				password text
		) $charsetCollate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		// set default options after installation:
		$settings = WiseChatContainer::get('WiseChatSettings');
		$settings->setDefaultSettings();

		self::upgradeMessagesTableCharset();
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

			if (strlen($charsetCollate) > 0) {
				$wpdb->query(sprintf('ALTER TABLE %s CONVERT TO %s;', self::getMessagesTable(), $charsetCollate));
			}
		}
	}
	
	public static function deactivate() {
		global $wpdb, $user_level, $sac_admin_user_level;
		
		if ($user_level < $sac_admin_user_level) {
			return;
		}
	}
	
	public static function uninstall() {
		if (!current_user_can('activate_plugins')) {
			return;
		}
        
        global $wpdb;
		
		// remove all messages and related images:
        /** @var WiseChatMessagesService $messagesService */
		$messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
        $messagesService->deleteAll();
		
		$tableName = self::getMessagesTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getBansTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getKicksTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getActionsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getChannelUsersTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		$tableName = self::getChannelsTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);

		$tableName = self::getUsersTable();
		$sql = "DROP TABLE IF EXISTS {$tableName};";
		$wpdb->query($sql);
		
		WiseChatOptions::getInstance()->dropAllOptions();
	}
}