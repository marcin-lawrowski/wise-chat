<?php

/**
 * Wise Chat command: /whois [userName]
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatWhoisCommand extends WiseChatAbstractCommand {
	public function execute() {
		$userName = isset($this->arguments[0]) ? $this->arguments[0] : null;
		if ($userName === null) {
            $this->addMessage('Please specify the user');
            return;
        }

        $user = $this->usersDAO->getLatestByName($userName);
        if ($user === null) {
            $this->addMessage(sprintf('User "%s" was not found', $userName));
            return;
        }

        $details = sprintf(
            "User: %s \n".
            "ID: %d \n".
            "IP address: %s \n".
            "Unique ID: %s \n",
            $userName, $user->getId(), $user->getIp(), $user->getSessionId()
        );

        $this->addMessage($details);
	}
}