<?php

/**
 * Wise Chat rendering class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatRenderer {
	
	/**
	* @var WiseChatMessagesService
	*/
	private $messagesService;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var WiseChatTemplater
	*/
	private $templater;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
		$this->channelUsersDAO = WiseChatContainer::get('dao/WiseChatChannelUsersDAO');
		WiseChatContainer::load('rendering/WiseChatTemplater');
		$this->templater = new WiseChatTemplater($this->options->getPluginBaseDir());
	}
	
	/**
	* Returns rendered channel statistics.
	*
	* @param WiseChatChannel $channel
	*
	* @return string HTML source
	*/
	public function getRenderedChannelStats($channel) {
		if ($channel === null) {
			return 'ERROR: channel does not exist';
		}

		$variables = array(
			'channel' => $channel->getName(),
			'messages' => $this->messagesService->getNumberByChannelName($channel->getName())
		);
	
		return $this->getTemplatedString($variables, $this->options->getOption('template', 'ERROR: TEMPLATE NOT SPECIFIED'));
	}
	
	public function getTemplatedString($variables, $template, $encodeValues = true) {
		foreach ($variables as $key => $value) {
			$template = str_replace("{".$key."}", $encodeValues ? urlencode($value) : $value, $template);
		}
		
		return $template;
	}

}