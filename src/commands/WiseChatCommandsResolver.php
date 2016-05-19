<?php

WiseChatContainer::load('commands/WiseChatAbstractCommand');

/**
 * WiseChat commands resolver.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatCommandsResolver {
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;

	/**
	 * @var WiseChatMessagesService
	 */
	private $messagesService;
	
	public function __construct() {
		$this->usersDAO = WiseChatContainer::get('dao/user/WiseChatUsersDAO');
		$this->messagesService = WiseChatContainer::get('services/WiseChatMessagesService');
	}

	/**
	* Checks whether given message is an admin command and executes it if so.
	*
	* @param WiseChatUser $user
	* @param WiseChatUser $systemUser
	* @param WiseChatChannel $channel Name of the channel
	* @param string $message Content of the possible command
	*
	* @return boolean True if the message is processed and is not needed to be displayed
	*/
	public function resolve($user, $systemUser, $channel, $message) {
		if ($this->isPotentialCommand($message) && $this->usersDAO->isWpUserAdminLogged()) {
			// print typed command (visible only for admins):
			$this->messagesService->addMessage($user, $channel, $message, true);
		
			// execute command:
			$resolver = $this->getCommandResolver($channel, $message);
			if ($resolver !== null) {
				$resolver->execute();
			} else {
				$this->messagesService->addMessage($systemUser, $channel, 'Command not found', true);
			}
		
			return true;
		}
		
		return false;
	}
	
	/**
	* Tokenizes command and returns command resolver.
	*
	* @param WiseChatChannel $channel Name of the channel
	* @param string $command The command
	*
	* @return WiseChatAbstractCommand
	*/
	private function getCommandResolver($channel, $command) {
        try {
            $commandClassName = $this->getClassNameFromCommand($command);
            WiseChatContainer::load("commands/{$commandClassName}");
            $tokens = $this->getTokenizedCommand($command);
            array_shift($tokens);

            return new $commandClassName($channel, $tokens);
        } catch (Exception $e) {
            return null;
        }
	}
	
	/**
	* Checks if a text can be recognized as a command.
	*
	* @param string $text The potential command
	*
	* @return boolean
	*/
	private function isPotentialCommand($text) {
		return strlen($text) > 0 && strpos($text, '/') === 0;
	}
	
	private function getTokenizedCommand($command) {
		$command = trim(trim($command), '/');
		$matches = array();
		preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $command, $matches);

		if (is_array($matches) && count($matches) > 0) {
			$matchesResult = array();
			foreach ($matches[0] as $match) {
				$matchesResult[] = trim($match, '"');
			}

			return $matchesResult;
		} else {
			return array();
		}
	}
	
	private function getClassNameFromCommand($command) {
		$tokens = $this->getTokenizedCommand($command);
		$commandName = str_replace('/', '', ucfirst($tokens[0]));
		
		return "WiseChat{$commandName}Command";
	}
}