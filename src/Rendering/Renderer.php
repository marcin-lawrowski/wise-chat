<?php

namespace Kainex\WiseChat\Rendering;

use Kainex\WiseChat\DAO\ChannelUsersDAO;
use Kainex\WiseChat\Model\Channel\Channel;
use Kainex\WiseChat\Services\MessagesService;
use Kainex\WiseChat\Options;

/**
 * Wise Chat rendering class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class Renderer {
	
	/**
	* @var MessagesService
	*/
	private $messagesService;
	
	/**
	* @var ChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var Options
	*/
	private $options;
	
	/**
	* @var Templater
	*/
	private $templater;

	/**
	 * @param MessagesService $messagesService
	 * @param ChannelUsersDAO $channelUsersDAO
	 * @param Options $options
	 * @param Templater $templater
	 */
	public function __construct(MessagesService $messagesService, ChannelUsersDAO $channelUsersDAO, Options $options, Templater $templater) {
		$this->messagesService = $messagesService;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->options = $options;
		$this->templater = $templater;
	}

	/**
	* Returns rendered channel statistics.
	*
	* @param Channel $channel
	*
	* @return string HTML source
	*/
	public function getRenderedChannelStats($channel) {
		if ($channel === null) {
			return 'ERROR: channel does not exist';
		}

		$variables = array(
			'channel' => $channel->getName(),
			'messages' => $this->messagesService->getNumberByChannelId($channel->getId())
		);
	
		return $this->getTemplatedString($variables, $this->options->getOption('template', 'ERROR: TEMPLATE NOT SPECIFIED'));
	}
	
	public function getTemplatedString($variables, $template, $encodeValues = true) {
		foreach ($variables as $key => $value) {
			$value = $value ?? '';
			$template = str_replace("{".$key."}", $encodeValues ? urlencode($value) : $value, $template);
		}

		$template = preg_replace('/{[0-9a-zA-Z_-]+}/', '', $template);
		
		return $template;
	}

}