<?php

namespace Kainex\WiseChat\Endpoints;

use Kainex\WiseChat\Endpoints\Commands\ChannelsBrowsing;
use Kainex\WiseChat\Endpoints\Utils\EndpointUtils;
use Kainex\WiseChat\Exceptions\UnauthorizedAccessException;

/**
 * User commands front controller.
 */
class CommandsEndpoint extends FrontEndpoint {

	private UserCommandEndpoint $userCommandEndpoint;
	private ChannelsBrowsing $channelsBrowsing;

	/**
	 * @param EndpointUtils $utils
	 * @param UserCommandEndpoint $userCommandEndpoint
	 * @param ChannelsBrowsing $channelsBrowsing
	 */
	public function __construct(EndpointUtils $utils, UserCommandEndpoint $userCommandEndpoint, Commands\ChannelsBrowsing $channelsBrowsing) {
		parent::__construct($utils);
		
		$this->userCommandEndpoint = $userCommandEndpoint;
		$this->channelsBrowsing = $channelsBrowsing;
	}

	public function handle() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = [];
		try {
			$this->utils->checkBanned();
			$this->utils->checkChatOpen();
			$this->utils->checkUserAuthentication();
			$this->utils->checkUserAuthorization();
			$this->checkPostParams(array('command', 'parameters'));

			$command = $this->getPostParam('command');
			$parameters = $this->getPostParam('parameters');
			if (str_starts_with($command, 'channels.browsing')) {
				$response = $this->channelsBrowsing->handle();
			} else {
				$response = $this->userCommandEndpoint->userCommandEndpoint();
			}
			$response['parameters'] = $parameters;
			$response['command'] = $command;
		} catch (UnauthorizedAccessException $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendUnauthorizedStatus();
		} catch (\Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

}