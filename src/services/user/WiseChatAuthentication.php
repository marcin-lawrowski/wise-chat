<?php

/**
 * Wise Chat authentication service.
 */
class WiseChatAuthentication {
    const SYSTEM_USER_NAME = 'System';
    const COOKIE_NAME = 'wc_auth_'.COOKIEHASH;
    const MINUTE_IN_SECONDS = 60;
    const HOUR_IN_SECONDS = 3600;
    const DAY_IN_SECONDS = 86400;
    const YEAR_IN_SECONDS = 31622400;

    /**
     * @var WiseChatUsersDAO
     */
    private $usersDAO;

    /**
     * @var WiseChatChannelUsersDAO
     */
    private $channelUsersDAO;

    /**
     * @var WiseChatUserService
     */
    private $userService;

    /**
     * @var WiseChatHttpRequestService
     */
    private $httpRequestService;

    /**
     * @var WiseChatOptions
     */
    private $options;

    /**
     * WiseChatAuthentication constructor.
     */
    public function __construct() {
        $this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
        $this->channelUsersDAO = WiseChatContainer::getLazy('dao/WiseChatChannelUsersDAO');
        $this->userService = WiseChatContainer::get('services/user/WiseChatUserService');
        $this->httpRequestService = WiseChatContainer::getLazy('services/WiseChatHttpRequestService');
        $this->options = WiseChatOptions::getInstance();
    }

    /**
     * Determines whether the current user is authenticated.
     *
     * @return boolean
     */
    public function isAuthenticated() {
        return $this->validateAuthenticationCookie() !== null;
    }

    /**
     * Determines whether the current user is authenticated externally.
     *
     * @return boolean
     */
    public function isAuthenticatedExternally() {
        return false;
    }

    /**
     * Returns authenticated user or null. The method is cached.
     *
     * @return WiseChatUser|null
     */
    public function getUser() {
        static $cache = null;

        if ($cache === null) {
            $cache = $this->validateAuthenticationCookie();
        }

        return $cache;
    }

    /**
     * Returns non-persistent system user.
     *
     * @return WiseChatUser
     */
    public function getSystemUser() {
        WiseChatContainer::load('model/WiseChatUser');

        $user = new WiseChatUser();
        $user->setId(0);
        $user->setName(self::SYSTEM_USER_NAME);
        $user->setSessionId(wp_generate_password());
		$user->setIp($this->getServerAddress());
		
        return $user;
    }

    /**
     * Returns authenticated username or empty string.
     *
     * @return string
     */
    public function getUserNameOrEmptyString() {
        $user = $this->getUser();

        return $user !== null ? $user->getName() : '';
    }

    /**
     * Returns authenticated user ID or null.
     *
     * @return integer|null
     */
    public function getUserIdOrNull() {
        $user = $this->getUser();

        return $user !== null ? $user->getId() : null;
    }

    /**
     * Authenticates anonymously the current user.
     *
     * @return WiseChatUser
     * @throws Exception
     */
    public function authenticateAnonymously() {
        if ($this->isAuthenticated()) {
            throw new Exception('Unsupported operation');
        }

        // generate new suffix for anonymous username:
        $userName = $this->getNextAnonymousUserName();

        return $this->createUserAndSave($userName);
    }

    /**
     * Returns new anonymous user name.
     *
     * @return string
     */
    public function getNextAnonymousUserName() {
        $userNameSuffix = $this->options->getUserNameSuffix() + 1;
        $this->options->setUserNameSuffix($userNameSuffix);

        return $this->options->getOption('user_name_prefix', 'Anonymous').$userNameSuffix;
    }

    /**
     * Authenticates user by username if no user is authenticated yet.
     *
     * @param string $userName
     *
     * @return WiseChatUser
     * @throws Exception If username cannot be set due to errors
     */
    public function authenticate($userName) {
        if ($this->isAuthenticated()) {
            throw new Exception('Unsupported operation');
        }
        $userName = $this->validateUserName($userName);

        return $this->createUserAndSave($userName);
    }

    /**
     * Authenticates user by user object if no user is authenticated yet.
     *
     * @param WiseChatUser $user
     *
     * @return WiseChatUser
     * @throws Exception
     */
    public function authenticateWithUser($user) {
        if ($this->isAuthenticated()) {
            throw new Exception('Could not authenticate user');
        }

        $user->setSessionId(wp_generate_password());
        $user->setIp($this->getRemoteAddress());
        if ($this->options->isOptionEnabled('collect_user_stats', true)) {
            $this->fillWithGeoDetails($user);
        }

        // save the user in the database and send auth cookie:
        $this->usersDAO->save($user);
        $this->sendAuthenticationCookie($user);

        return $user;
    }

	/**
	 * Authenticates user using given WP user object.
	 *
	 * @param WP_User $wpUser
	 * @return WiseChatUser
	 * @throws Exception
	 */
    public function authenticateWithWpUser($wpUser) {
	    $user = $this->usersDAO->getLatestByWordPressId($wpUser->ID);
	    if ($user !== null) {
		    $this->authenticateWithUser($user);
	    } else {
		    $user = $this->authenticateAnonymously();
		    $user->setWordPressId(intval($wpUser->ID));
	    }

	    // save user name based on WP account:
	    $user->setName($this->usersDAO->getChatUserNameFromWpUser($wpUser));
	    $this->usersDAO->save($user);

	    return $user;
    }

    /**
     * Drops the authentication.
     */
    public function dropAuthentication() {
    	$this->channelUsersDAO->setStatus($this->getUserIdOrNull(), false);
        $this->clearAuthenticationCookie();
    }

    /**
     * Validates given username.
     *
     * @param string $userName
     *
     * @return string Validated username (trimmed and filtered)
     * @throws Exception If username is not valid
     */
    public function validateUserName($userName) {
        $userName = trim($userName);

        // check for valid characters:
        if (!$userName || !preg_match('/^[\p{L}a-zA-Z0-9\-_ ’]+$/u', $userName)) {
            throw new Exception($this->options->getOption('message_error_1', __('Only letters, number, spaces, hyphens and underscores are allowed', 'wise-chat')));
        }

        // filter the new username:
        if ($this->options->isOptionEnabled('filter_bad_words')) {
            WiseChatContainer::load('rendering/filters/pre/WiseChatFilter');
            $userName = WiseChatFilter::filter($userName);
        }

        // check if the new username is already occupied:
        $occupiedException = new Exception($this->options->getOption('message_error_2', __('This name is already occupied', 'wise-chat')));
        $prefix = $this->options->getOption('user_name_prefix', __('Anonymous', 'wise-chat'));
        $disableUserNameCheck = $this->options->isOptionEnabled('disable_user_name_duplication_check', true);
        if (
            $this->getUserNameOrEmptyString() == $userName ||
            $this->usersDAO->getWpUserByDisplayName($userName) !== null ||
            $this->usersDAO->getWpUserByLogin($userName) !== null ||
            $this->channelUsersDAO->isUserNameOccupied($userName, $disableUserNameCheck) ||
            preg_match("/^{$prefix}/", $userName) ||
            $userName == $this->getSystemUser()->getName()
        ) {
            throw $occupiedException;
        }

        return $userName;
    }

    /**
     * Sends authentication cookie.
     *
     * @param WiseChatUser $user
     */
    private function sendAuthenticationCookie($user) {
        $expiration = $this->getAuthenticationCookieExpirationTime();
        $authCookieValue = $this->getAuthenticationCookieValue($user, $expiration);

        $expire = $expiration > 0 ? $expiration + (12 * self::HOUR_IN_SECONDS) : 0;
        $secureLoggedInCookie = is_ssl() && 'https' === parse_url(get_option('home'), PHP_URL_SCHEME);

        setcookie(self::COOKIE_NAME, $authCookieValue, $expire, COOKIEPATH, COOKIE_DOMAIN, $secureLoggedInCookie, true);
        if (COOKIEPATH != SITECOOKIEPATH) {
            setcookie(self::COOKIE_NAME, $authCookieValue, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secureLoggedInCookie, true);
        }

        // set the cookie for further processing in the current request:
        $_COOKIE[self::COOKIE_NAME] = $authCookieValue;
    }

    /**
     * Clears authentication cookie.
     */
    private function clearAuthenticationCookie() {
        setcookie(self::COOKIE_NAME, ' ', time() - self::YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        setcookie(self::COOKIE_NAME, ' ', time() - self::YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Returns authentication cookie value.
     *
     * @param WiseChatUser $user
     * @param string $expiration
     *
     * @return string
     */
    private function getAuthenticationCookieValue($user, $expiration) {
        $key = wp_hash($user->getId() . '|' . $user->getSessionId() . '|'. $expiration, 'auth');
        $hash = hash_hmac(function_exists('hash') ? 'sha256' : 'sha1', $user->getId() . '|' . $expiration, $key);

        return $user->getId() . '|' . $expiration . '|' . $hash;
    }

    private function getAuthenticationCookieExpirationTime() {
        $timeout = $this->options->getIntegerOption('user_auth_expiration_days', 14);
        if ($timeout === 0) {
            return 0;
        }

        return time() + $timeout * self::DAY_IN_SECONDS;
    }

    /**
     * Returns the authenticated user or null.
     *
     * @return WiseChatUser|null
     */
    private function validateAuthenticationCookie() {
        if (!is_array($_COOKIE) || !array_key_exists(self::COOKIE_NAME, $_COOKIE) || !$_COOKIE[self::COOKIE_NAME]) {
            return null;
        }

        $cookie = $_COOKIE[self::COOKIE_NAME];
        $cookieElements = explode('|', $cookie);
        if (count($cookieElements) !== 3) {
            return null;
        }

        $userId = $cookieElements[0];
        $expiration = $cookieElements[1];
        $hashMac = $cookieElements[2];

        if ($expiration > 0 && $expiration < time()) {
            return null;
        }

        $user = $this->usersDAO->get($userId);
        if ($user === null) {
            return null;
        }

        $key = wp_hash($user->getId().'|'.$user->getSessionId().'|'.$expiration, 'auth');
        $hash = hash_hmac(function_exists('hash') ? 'sha256' : 'sha1', $user->getId().'|'.$expiration, $key);
        if (!hash_equals($hash, $hashMac)) {
            return null;
        }

        $this->refreshAuthenticationCookie($expiration, $user);

        return $user;
    }

    /**
     * Refresh the cookie if expiration time is less than half.
     *
     * @param integer $expiration
     * @param WiseChatUser $user
     */
    private function refreshAuthenticationCookie($expiration, $user) {
        $timeout = $this->options->getIntegerOption('user_auth_expiration_days', 14);
        if ($expiration === 0 || headers_sent() || $timeout === 0 || !$this->options->isOptionEnabled('user_auth_keep_logged_in', true)) {
            return;
        }

        $half = $timeout * self::DAY_IN_SECONDS / 2;
        $lifeTime = $expiration - time();
        if ($lifeTime < $half) {
            header("X-Wise-Chat-Pro: refreshed cookie $lifeTime < $half");
            $this->sendAuthenticationCookie($user);
        }
    }

    /**
     * @param string $userName
     *
     * @return WiseChatUser
     */
    private function createUserAndSave($userName) {
        WiseChatContainer::load('model/WiseChatUser');

        // construct username and user object:
        $user = new WiseChatUser();
        $user->setName($userName);

        return $this->authenticateWithUser($user);
    }

    /**
     * @param WiseChatUser $user
     */
    private function fillWithGeoDetails($user) {
        /** @var WiseChatGeoService $geoService */
        $geoService = WiseChatContainer::get('services/WiseChatGeoService');
        $geoDetails = $geoService->getGeoDetails($this->getRemoteAddress());
        if ($geoDetails !== null) {
            $geoDetailsArray = $geoDetails->toArray();
            foreach ($geoDetailsArray as $key => $value) {
                $user->setDataProperty($key, $value);
            }
        }
    }

    /**
     * Returns remote address.
     *
     * @return string
     */
    private function getRemoteAddress() {
		$ipProposal = null;
	    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
		    $ipAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

		    $ipProposal = trim($ipAddresses[0]);
	    } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
		    $ipProposal = $_SERVER["REMOTE_ADDR"];
	    } else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
		    $ipProposal = $_SERVER["HTTP_CLIENT_IP"];
	    }

		if ($ipProposal === null) {
			return '';
		}

		if (filter_var($ipProposal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ipProposal, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return $ipProposal;
		}

	    return '';
    }
    
    /**
     * Returns server address.
     *
     * @return string
     */
    private function getServerAddress() {
	    if (is_array($_SERVER) && array_key_exists('SERVER_ADDR', $_SERVER)) {
		    return $_SERVER['SERVER_ADDR'];
	    }
	    if (is_array($_SERVER) && array_key_exists('LOCAL_ADDR', $_SERVER)) {
		    return $_SERVER['LOCAL_ADDR'];
	    }

	    return '';
    }
}