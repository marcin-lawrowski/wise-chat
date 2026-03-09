<?php

namespace Kainex\WiseChat\DAO\User;

use Exception;
use Kainex\WiseChat\DAO\AbstractDAO;
use Kainex\WiseChat\Model\User;
use Kainex\WiseChat\Installer;
use Kainex\WiseChat\Options;

/**
 * WiseChat and WordPress users DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UsersDAO extends AbstractDAO {
	/**
	* @var Options
	*/
	private $options;

	/**
	 * @var array
	 */
	private $usersCache;

	private static array $wpUsersCache = [];

	/**
	 * @var array
	 */
	private $usersSetCache;

	/**
	 * @var string[] Legacy rights names conversion map
	 */
	private static $rightsConversionMap = array(
		'approve_message' => 'approve',
		'edit_message' => 'edit',
		'delete_message' => 'delete',
		'mute_user' => 'mute',
		'ban_user' => 'ban',
		'spam_report' => 'spam',
		'create_channels' => 'create_channels',
		'search_channels' => 'search_channels'
	);

	/**
	 * @param Options $options
	 */
	public function __construct(Options $options) {
		$this->options = $options;
		$this->usersCache = array();
		$this->usersSetCache = array();
	}

	protected function getTableName(): string {
		return Installer::getUsersTable();
	}

	/**
	 * Returns user by ID.
	 *
	 * @param integer $id
	 *
	 * @return User|null
	 */
	public function get($id) {
		global $wpdb;

		if (array_key_exists($id, $this->usersCache)) {
			return $this->usersCache[$id];
		}

		$table = Installer::getUsersTable();
		$sql = sprintf('SELECT * FROM %s WHERE id = %d;', $table, $id);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$user = $this->populateData($results[0]);
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
	 * @return User|null
	 */
	public function getLatestByName($name) {
		global $wpdb;

		$table = Installer::getUsersTable();
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
	 * @return User|null
	 */
	public function getLatestByWordPressId($wpUserId) {
		global $wpdb;

		$table = Installer::getUsersTable();
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
	 * @param \WP_User[] $wpUsers
	 * @return User[]
	 */
	public function getLatestChatUsersByWordPressIds($wpUsers) {
		global $wpdb;
		$resultMap = array();
		$table = Installer::getUsersTable();

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
					$resultMap[$result->wp_id] = $this->populateData($result);
				}
			}
		}

		return $resultMap;
	}

	/**
	 * Returns the latest user by external login details.
	 *
	 * @param string $externalType
	 * @param string $externalId
	 * @return null|User
	 */
	public function getByExternalTypeAndId($externalType, $externalId) {
		global $wpdb;

		$table = Installer::getUsersTable();
		$sql = sprintf("SELECT max(id) AS id FROM %s WHERE external_type = '%s' AND external_id = '%s';", $table, addslashes($externalType), addslashes($externalId));
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];
			return $this->get($result->id);
		}

		return null;
	}

	/**
	 * Returns users by IDs.
	 *
	 * @param array $ids Array of IDs
	 *
	 * @return User[]
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
		$table = Installer::getUsersTable();
		$sql = sprintf('SELECT * FROM %s WHERE id IN (%s);', $table, implode(',', $idsFiltered));
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$users[] = $this->populateData($result);
			}
		}

		$this->usersSetCache[$key] = $users;

		return $users;
	}

	/**
	 * Creates or updates the user and returns it.
	 *
	 * @param User $user
	 *
	 * @return User
	 * @throws \Exception On validation error
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
		$table = Installer::getUsersTable();
		$columns = array(
			'name' => $user->getName(),
			'session_id' => $user->getSessionId(),
			'external_type' => $user->getExternalType(),
			'external_id' => $user->getExternalId(),
			'avatar_url' => $user->getAvatarUrl(),
			'profile_url' => $user->getProfileUrl(),
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
	 * Returns the chat username based on 'username_source' configuration field.
	 * It falls back to display_name.
	 *
	 * @param \WP_User $wpUser
	 * @return string
	 */
	public function getChatUserNameFromWpUser($wpUser) {
		$userNameSource = $this->options->getOption('username_source', 'display_name');
		$fieldValue = trim($wpUser->$userNameSource);

		if ($fieldValue) {
			return $fieldValue;
		}

		$userNameSourceFallBack = $this->options->getOption('username_source_fallback', 'display_name');

		return $wpUser->$userNameSourceFallBack;
	}

	/**
	 * Converts raw object into WiseChatUser object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return User
	 */
	protected function populateData(\stdClass $rawRow): object {
		$user = new User();
		if ($rawRow->id) {
			$user->setId(intval($rawRow->id));
		}
        if ($rawRow->wp_id) {
            $user->setWordPressId(intval($rawRow->wp_id));
        }
		$user->setName($rawRow->name);
		$user->setSessionId($rawRow->session_id);
		$user->setExternalType($rawRow->external_type);
		$user->setExternalId($rawRow->external_id);
		$user->setAvatarUrl($rawRow->avatar_url);
		$user->setProfileUrl($rawRow->profile_url);
		$user->setIp($rawRow->ip);
		$user->setData(json_decode($rawRow->data, true));

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
		}
		
		return false;
	}

	/**
	 * @param integer $wpUserId
	 * @param string $key
	 * @return mixed
	 */
	public function getWpUserMeta($wpUserId, $key) {
		return get_user_meta($wpUserId, $key, true);
	}

	/**
	 * Determines whether current BuddyPress user has given right.
	 *
	 * @param string $rightName
	 *
	 * @return boolean
	 */
	public function hasCurrentBpUserRight($rightName) {
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
	* @return \WP_User|null
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
			$users = new \WP_User_Query($args);
			if (count($users->results) > 0) {
				$cache[$displayName] = $users->results[0];
			} else {
				$cache[$displayName] = null;
			}
		}
		
		return $cache[$displayName];
	}
	
	/**
	* Returns WordPress user by ID.
	* All results are cached for later use.
	*
	* @param integer|null $id
	*
	* @return \WP_User|null
	*/
	public function getWpUserByID(?int $id): ?\WP_User {
		if ($id === null) {
			return null;
		}
		if (array_key_exists($id, self::$wpUsersCache)) {
			return self::$wpUsersCache[$id];
		}

		$this->cacheWPUsers(['include' => [$id]]);
		if (array_key_exists($id, self::$wpUsersCache)) {
			return self::$wpUsersCache[$id];
		}

		return null;
	}
	
	/**
	* Returns WordPress user by its "user_login" field.
	* All results are cached in static field.
	*
	* @param string $userLogin
	*
	* @return \WP_User|null
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
		$users = new \WP_User_Query($args);
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
	* @return \WP_User|null
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

		$table = Installer::getUsersTable();
		$sql = sprintf("DELETE FROM %s;", $table);
		$wpdb->get_results($sql);
	}

	/**
	 * @param array $parameters
	 * @return \WP_User[]
	 */
	public function getWPUsers($parameters) {
		$hideRoles = $this->options->getOption('users_list_hide_roles', array());
		$args = array(
			'orderby' => 'display_name',
			'fields' => 'all_with_meta',
			'role__not_in' => is_array($hideRoles) ? $hideRoles : array()
		);
		foreach ($parameters as $key => $value) {
			$args[$key] = $value;
		}
		$usersCacheTime = $this->options->getIntegerOption('users_cache_time', 1200);
		if ($usersCacheTime > 0) {
			$transientKey = 'wise_chat_pro_wp_users_cache_'.sha1(serialize($parameters));
			$wpUsers = get_transient($transientKey);
			if (false === $wpUsers || get_transient('wise_chat_pro_wp_users_cache_reset') !== false) {
				$wpUsers = get_users($args);
				set_transient($transientKey, $wpUsers, $usersCacheTime);
				delete_transient('wise_chat_pro_wp_users_cache_reset');
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

	/**
	 * Reads and stores given WP users in cache for further access.
	 *
	 * @param array $parameters get_users() function arguments
	 */
	public function cacheWPUsers(array $parameters): void {
		$args = [
			'fields' => 'all'
		];
		foreach ($parameters as $key => $value) {
			$args[$key] = $value;
		}

		if (isset($args['include'])) {
			if (is_array($args['include'])) {
				$args['include'] = array_diff($args['include'], array_keys(self::$wpUsersCache));
				if (empty($args['include'])) {
					return;
				}
			} else {
				if (isset($this->wpUsersCache[$args['include']])) {
					return;
				}
			}

		}

		foreach (get_users($args) as $wpUser) {
			self::$wpUsersCache[$wpUser->ID] = $wpUser;
		}
	}

	/**
	 * Reads and stores given users in cache for further access.
	 *
	 * @param int[] $userIDs
	 */
	public function cacheUsers(array $userIDs): void {
		if (empty($userIDs)) {
			return;
		}

		$rawData = $this->getAllBy(['id' => [$userIDs, '%d']], ['id', 'asc']);

		/** @var User[] $users */
		$users = array_map([$this, 'populateData'], $rawData);
		foreach ($users as $user) {
			$this->usersCache[$user->getId()] = $user;
		}
	}

	/**
	 * @param array $options
	 * @return User[]
	 */
	public function getOnlineUsers(array $options = []): array {
		$limitToUserIDs = $options['limitToUserIDs'] ?? [];
		$limitToWordPressUserIDs = $options['limitToWordPressUserIDs'] ?? [];

		$conditions = ['cu.active' => 1];
		if (!empty($limitToWordPressUserIDs)) {
			$conditions['main.wp_id'] = [$limitToWordPressUserIDs, '%d'];
		}
		if (!empty($limitToUserIDs)) {
			$conditions['main.id'] = [$limitToUserIDs, '%d'];
		}
		$rawData = $this->getAllBy($conditions, ['name', 'asc'], null, null, [
			'select' => 'main.*',
			'join' => [[Installer::getChannelUsersTable(), 'cu', ['main.id = cu.user_id']]]
		]);

		return array_map([$this, 'populateData'], $rawData);
	}
}