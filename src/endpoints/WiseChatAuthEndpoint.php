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
		if (!$this->authentication->isAuthenticated() && $this->options->isOptionEnabled('force_user_name_selection', false)) {
            $user = $this->authentication->authenticate($name);
        }

        if ($user === null) {
            throw new Exception('Authentication error');
        }

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
            throw new Exception($this->options->getOption('message_error_9', 'Invalid password.'));
        }
	}

}