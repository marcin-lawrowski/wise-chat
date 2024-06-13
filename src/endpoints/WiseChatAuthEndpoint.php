<?php

WiseChatContainer::load('endpoints/WiseChatEndpoint');

/**
 * Wise Chat auth endpoint class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatAuthEndpoint extends WiseChatEndpoint {

	/** @var WiseChatMaintenanceAuth */
	private $maintenanceAuth;

	public function __construct() {
		parent::__construct();

		/** @var WiseChatMaintenanceAuth maintenanceAuth */
		$this->maintenanceAuth = WiseChatContainer::getLazy('endpoints/maintenance/WiseChatMaintenanceAuth');
	}

	/**
	 * Auth endpoint.
	 */
	public function authEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkIpNotKicked();
			$this->checkChatOpen();

			$this->checkPostParams(array('mode', 'parameters'));

			$mode = $this->getPostParam('mode');
			$parameters = $this->getPostParam('parameters');
			switch ($mode) {
				case 'username':
					$this->doUserNameAuth($parameters);
					break;
				case 'anonymous':
					$this->doAnonymousAuth($parameters);
					break;
				case 'channel-password':
					$this->doChannelPasswordAuth($parameters);
					break;
				default:
					throw new \Exception('Unknown auth method');
			}

			$response['parameters'] = $parameters;
			$response['mode'] = $mode;
			$response['user'] = $this->maintenanceAuth->getUser();
		} catch (WiseChatUnauthorizedAccessException $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

	/**
	 * @param array $parameters
	 * @return WiseChatUser
	 * @throws Exception
	 */
	private function doUserNameAuth($parameters) {
		$name = $parameters['name'];
		$nonce = $parameters['nonce'];

		$nonceAction = 'un'.$this->httpRequestService->getRemoteAddress();

		if (!wp_verify_nonce($nonce, $nonceAction)) {
			throw new Exception('Bad request');
        }

		$user = null;
		if (!$this->authentication->isAuthenticated() && $this->options->getOption('auth_mode', 'auto') === 'username') {
            $user = $this->authentication->authenticate($name);
        }

        if ($user === null) {
            throw new Exception('Authentication error');
        }

        if (array_key_exists('fields', $parameters)) {
        	$fieldsInput = $parameters['fields'];
	        $fields = array_filter(json_decode($this->options->getOption('auth_username_fields', '[]')), function ($field) {
		        return $field->name ? true : false;
	        });

	        if (count($fields) > 0) {
		        $fieldsToSave = array();
		        foreach ($fields as $field) {
			        $id = $field->id;
			        if (array_key_exists($id, $fieldsInput)) {
				        $fieldsToSave[$id] = strip_tags($fieldsInput[$id]);
			        }
		        }
		        $user->setDataProperty('fields', $fieldsToSave);
		        $this->usersDAO->save($user);
	        }
        }

        /**
         * Fires once user has started its session in the chat.
         *
         * @since 2.3.2
         *
         * @param WiseChatUser $user The user object
         */
        do_action("wc_user_session_started", $user);

        return $user;
	}

	/**
	 * @param array $parameters
	 * @return WiseChatUser
	 * @throws Exception
	 */
	private function doAnonymousAuth($parameters) {
		$nonce = $parameters['nonce'];

		$nonceAction = 'an'.$this->httpRequestService->getRemoteAddress();

		if (!wp_verify_nonce($nonce, $nonceAction)) {
			throw new Exception('Bad request');
        }

		$user = null;
		if (!$this->authentication->isAuthenticated() && $this->options->isOptionEnabled('anonymous_login_enabled', true)) {
            $user = $this->authentication->authenticateAnonymously();
        }

        if ($user === null) {
            throw new Exception('Authentication error');
        }

        /**
         * Fires once user has started its session in the chat.
         *
         * @since 2.3.2
         *
         * @param WiseChatUser $user The user object
         */
        do_action("wc_user_session_started", $user);

        return $user;
	}

	/**
	 * @param array $parameters
	 * @throws Exception
	 */
	private function doChannelPasswordAuth($parameters) {
		$password = $parameters['password'];
		$channelId = $parameters['channelId'];

		if (!$this->authentication->isAuthenticated()) {
            throw new Exception('Authentication error');
        }

		$channel = $this->getChannelFromEncryptedId($channelId);
		if ($channel === null) {
            throw new Exception('Authentication error - unknown channel');
        }

		if ($channel->getPassword() === md5($password)) {
            $this->authorization->markAuthorizedForChannel($channel);
        } else {
            throw new Exception($this->options->getOption('message_error_9', __('Invalid password.', 'wise-chat')));
        }
	}

}