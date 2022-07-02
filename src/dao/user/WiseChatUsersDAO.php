<?php

/**
 * WiseChat and WordPress users DAO.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatUsersDAO {
	/**
	* @var WiseChatOptions
	*/
	private $options;

	/**
	 * @var array
	 */
	private $usersCache;

	/**
	 * @var array
	 */
	private $usersSetCache;

	/**
	 * @var string[] Legacy rights names conversion map
	 */
	private static $rightsConversionMap = array(
		'delete_message' => 'delete',
		'ban_user' => 'mute',
		'kick_user' => 'ban',
		'spam_report' => 'spam'
	);

	public function __construct() {
		WiseChatContainer::load('model/WiseChatUser');
		$this->options = WiseChatOptions::getInstance();
		$this->usersCache = array();
		$this->usersSetCache = array();
	}

	/**
	 * Returns user by ID.
	 *
	 * @param integer $id
	 *
	 * @return WiseChatUser|null
	 */
	public function get($id) {
		global $wpdb;

		if (array_key_exists($id, $this->usersCache)) {
			return $this->usersCache[$id];
		}

		$table = WiseChatInstaller::getUsersTable();
		$sql = sprintf('SELECT * FROM %s WHERE id = %d;', $table, $id);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$user = $this->populateUserData($results[0]);
			$this->usersCache[$id] = $user;

			return $user;
		}

		$this->usersCache[$id] = null;

		return null;
	}

	/**
	 * Returns the latest (according the ID field) user by specified name.
	 *
	 * @param string $name
	 *
	 * @return WiseChatUser|null
	 */
	public function getLatestByName($name) {
		global $wpdb;

		$table = WiseChatInstaller::getUsersTable();
		$sql = sprintf("SELECT max(id) AS id FROM %s WHERE name = '%s';", $table, addslashes($name));
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $this->get($result->id);
		}

		return null;
	}

	/**
	 * Returns the latest (according the ID field) user by specified WordPress user ID.
	 *
	 * @param integer $wpUserId
	 *
	 * @return WiseChatUser|null
	 */
	public function getLatestByWordPressId($wpUserId) {
		global $wpdb;

		$table = WiseChatInstaller::getUsersTable();
		$sql = sprintf("SELECT max(id) AS id FROM %s WHERE wp_id = '%d';", $table, $wpUserId);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];

			return $result->id !== null ? $this->get($result->id) : null;
		}

		return null;
	}

	/**
	 * Returns the latest (according to ID field) chat users for given WP users.
	 *
	 * @param WP_User[] $wpUsers
	 * @return WiseChatUser[]
	 */
	public function getLatestChatUsersByWordPressIds($wpUsers) {
		global $wpdb;
		$resultMap = array();
		$table = WiseChatInstaller::getUsersTable();

		$chunks = array_chunk($wpUsers, 500, false);
		foreach ($chunks as $chunk) {
			$ids = array_map(function($wpUser) {
				return $wpUser->ID;
			}, $chunk);

			if (count($ids) === 0) {
				continue;
			}

			$sql = sprintf("SELECT * FROM %s WHERE id IN(SELECT max(id) FROM %s WHERE wp_id IN (%s) GROUP BY wp_id);", $table, $table, implode(', ', $ids));
			$results = $wpdb->get_results($sql);
			if (is_array($results)) {
				foreach ($results as $result) {
					$resultMap[$result->wp_id] = $this->populateUserData($result);
				}
			}
		}

		return $resultMap;
	}

	/**
	 * Returns users by IDs.
	 *
	 * @param array $ids Array of IDs
	 *
	 * @return WiseChatUser[]
	 */
	public function getAll($ids) {
		global $wpdb;

		if (!is_array($ids) || count($ids) == 0) {
			return array();
		}
        $idsFiltered = array();
        foreach ($ids as $id) {
            if ($id > 0) {
                $idsFiltered[] = $id;
            }
        }

		if (count($idsFiltered) === 0) {
			return array();
		}

		$key = sha1(implode(',', $idsFiltered));
		if (array_key_exists($key, $this->usersSetCache)) {
			return $this->usersSetCache[$key];
		}

		$users = array();
		$table = WiseChatInstaller::getUsersTable();
		$sql = sprintf('SELECT * FROM %s WHERE id IN (%s);', $table, implode(',', $idsFiltered));
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$users[] = $this->populateUserData($result);
			}
		}

		$this->usersSetCache[$key] = $users;

		return $users;
	}

	/**
	 * Creates or updates the user and returns it.
	 *
	 * @param WiseChatUser $user
	 *
	 * @return WiseChatUser
	 * @throws Exception On validation error
	 */
	public function save($user) {
		global $wpdb;

		// low-level validation:
		if ($user->getName() === null) {
			throw new Exception('Name of the user cannot equal null');
		}
		if ($user->getSessionId() === null) {
			throw new Exception('Session ID of the user cannot equal null');
		}

		// prepare user data:
		$table = WiseChatInstaller::getUsersTable();
		$columns = array(
			'name' => $user->getName(),
			'session_id' => $user->getSessionId(),
			'avatar_url' => $user->getAvatarUrl(),
			'data' => json_encode($user->getData()),
			'ip' => $user->getIp()
		);

		// update or insert:
		if ($user->getId() !== null) {
			$columns['wp_id'] = $user->getWordPressId();
			$wpdb->update($table, $columns, array('id' => $user->getId()), '%s', '%d');
		} else {
			if ($user->getWordPressId() > 0) {
				$columns['wp_id'] = $user->getWordPressId();
			}
			$columns['created'] = time();
			$wpdb->insert($table, $columns);
			$user->setId($wpdb->insert_id);
		}

		// refresh cache:
		$this->usersCache[$user->getId()] = $user;

		return $user;
	}

	/**
	 * Updates the name by specified WordPress user ID.
	 *
	 * @param string $name
	 * @param integer $wpUserId
	 */
	public function updateNameByWordPressId($name, $wpUserId) {
		global $wpdb;

		$table = WiseChatInstaller::getUsersTable();
		$wpdb->update($table, array('name' => $name), array('wp_id' => $wpUserId), '%s', '%d');
	}

	/**
	 * Creates chat user based on given WordPress user ID.
	 *
	 * @param $wordPressUserId
	 * @return null|WiseChatUser
	 * @throws Exception
	 */
	public function createOrGetBasedOnWordPressUserId($wordPressUserId) {
		$chatUser = $this->getLatestByWordPressId($wordPressUserId);
		if ($chatUser !== null) {
			return $chatUser;
		}

		$wordPressUser = $this->getWpUserByID($wordPressUserId);
		if ($wordPressUser !== null) {
			$chatUser = new WiseChatUser();
			$chatUser->setName($this->getChatUserNameFromWpUser($wordPressUser));
			$chatUser->setWordPressId($wordPressUser->ID);
			$chatUser->setSessionId(wp_generate_password());

			return $this->save($chatUser);
		}

		return null;
	}

	/**
	 * Returns the chat username based on 'username_source' configuration field.
	 * It falls back to display_name.
	 *
	 * @param WP_User $wpUser
	 * @return string
	 */
	public function getChatUserNameFromWpUser($wpUser) {
		$userNameSource = $this->options->getOption('username_source', 'display_name');
		$fieldValue = trim($wpUser->$userNameSource);

		if (strlen($fieldValue) > 0) {
			return $fieldValue;
		}

		$userNameSourceFallBack = $this->options->getOption('username_source_fallback', 'display_name');

		return $wpUser->$userNameSourceFallBack;
	}

	/**
	 * Converts raw object into WiseChatUser object.
	 *
	 * @param stdClass $rawUserData
	 *
	 * @return WiseChatUser
	 */
	private function populateUserData($rawUserData) {
		$user = new WiseChatUser();
		if (strlen($rawUserData->id) > 0) {
			$user->setId(intval($rawUserData->id));
		}
        if (strlen($rawUserData->wp_id) > 0) {
            $user->setWordPressId(intval($rawUserData->wp_id));
        }
		$user->setName($rawUserData->name);
		$user->setSessionId($rawUserData->session_id);
		$user->setAvatarUrl($rawUserData->avatar_url);
		$user->setIp($rawUserData->ip);
		$user->setData(json_decode($rawUserData->data, true));

		return $user;
	}

	/**
	* Detects whether a WordPress admin is logged in.
	*
	* @return boolean
	*/
	public function isWpUserAdminLogged() {
		return current_user_can('manage_options');
	}
	
	/**
	* Determines whether the current user has the given right.
	*
	* @param string $rightName
	*
	* @return boolean
	*/
	public function hasCurrentWpUserRight($rightName) {
		$wpUser = $this->getCurrentWpUser();
		
		if ($wpUser !== null) {
			$targetRoles = (array) $this->options->getOption("permission_{$rightName}_role", 'administrator');
			if ((is_array($wpUser->roles) && count(array_intersect($targetRoles, $wpUser->roles)) > 0) || current_user_can("wise_chat_{$rightName}")) {
				return true;
			}

			// check the individual rights:
			$right = self::$rightsConversionMap[$rightName];
			$moderators = (array) $this->options->getOption('moderators', array());
			foreach ($moderators as $moderator) {
				if ($moderator['userId'] !== $wpUser->ID) {
					continue;
				}

				if (is_array($moderator['rights']) && in_array($right, $moderator['rights'])) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	* Checks if WordPress user is logged in.
	*
	* @return boolean
	*/
	public function isWpUserLogged() {
		if (is_user_logged_in()) {
			return true;
		}
		
		return false;
	}
	
	/**
	* Returns WordPress user by its "display_name" field.
	* All results are cached in static field for later use.
	*
	* @param string $displayName
	*
	* @return WP_User|null
	*/
	public function getWpUserByDisplayName($displayName) {
		global $wpdb;
		static $cache = array();
		
		if (array_key_exists($displayName, $cache)) {
			return $cache[$displayName];
		}

		$userRow = $wpdb->get_row($wpdb->prepare(
			"SELECT `ID` FROM {$wpdb->users} WHERE `display_name` = %s", $displayName
		));
		if ($userRow === null) {
			$cache[$displayName] = null;
		} else {
			$args = array(
				'search' => $userRow->ID,
				'search_columns' => array('ID')
			);
			$users = new WP_User_Query($args);
			if (count($users->results) > 0) {
				$cache[$displayName] = $users->results[0];
			} else {
				$cache[$displayName] = null;
			}
		}
		
		return $cache[$displayName];
	}
	
	/**
	* Returns WordPress user by its ID field.
	* All results are cached in static field for later use.
	*
	* @param integer $id
	*
	* @return WP_User|null
	*/
	public function getWpUserByID($id) {
		static $cache = array();
		
		if (array_key_exists($id, $cache)) {
			return $cache[$id];
		}
		
		$user = get_user_by('id', $id);
		$cache[$id] = $user !== false ? $user : null;
		
		return $cache[$id];
	}
	
	/**
	* Returns WordPress user by its "user_login" field.
	* All results are cached in static field.
	*
	* @param string $userLogin
	*
	* @return WP_User|null
	*/
	public function getWpUserByLogin($userLogin) {
		static $cache = array();
		
		if (array_key_exists($userLogin, $cache)) {
			return $cache[$userLogin];
		}
		
		$args = array(
			'search' => $userLogin,
			'search_columns' => array('user_login')
		);
		$users = new WP_User_Query($args);
		if (count($users->results) > 0) {
			$cache[$userLogin] = $users->results[0];
		} else {
			$cache[$userLogin] = null;
		}

		return $cache[$userLogin];
	}
	
	/**
	* Returns current WordPress user or null if nobody is logged in.
	*
	* @return WP_User|null
	*/
	public function getCurrentWpUser() {
		if (is_user_logged_in()) {
			return wp_get_current_user();
		}
		
		return null;
	}

	/**
	 * Deletes all users.
	 */
	public function deleteAll() {
		global $wpdb;

		$table = WiseChatInstaller::getUsersTable();
		$sql = sprintf("DELETE FROM %s;", $table);
		$wpdb->get_results($sql);
	}

	/**
	 * @param integer[] $limitToUsers
	 * @return WP_User[]
	 */
	public function getWPUsers($limitToUsers = array()) {
		$hideRoles = $this->options->getOption('users_list_hide_roles', array());
		$args = array(
			'orderby' => 'display_name',
			'fields' => 'all_with_meta',
			'role__not_in' => is_array($hideRoles) ? $hideRoles : array()
		);
		if (count($limitToUsers) > 0) {
			$args['include'] = $limitToUsers;
		}
		$usersCacheTime = $this->options->getIntegerOption('users_cache_time', 1200);
		if ($usersCacheTime > 0) {
			$transientKey = 'wise_chat_wp_users_cache';
			if (false === ($wpUsers = get_transient($transientKey))) {
				$wpUsers = get_users($args);
				set_transient($transientKey, $wpUsers, $usersCacheTime);
			}
		} else {
			$wpUsers = get_users($args);
		}

		// load and cache users meta:
		if ($this->options->isOptionEnabled('internal_cache_users_meta_cache_force', true) && is_array($wpUsers)) {
			$chunks = array_chunk($wpUsers, 200, false);
			foreach ($chunks as $chunk) {
				$ids = array_map(function ($wpUser) {
					// force adding WP_User to cache to avoid future database queries:
					if ($this->options->isOptionEnabled('internal_cache_users_cache_force', true)) {
						update_user_caches($wpUser);
					}

					return $wpUser->ID;
				}, $chunk);
				update_meta_cache('user', $ids);
			}
		}

		return is_array($wpUsers) ? $wpUsers : array();
	}
}