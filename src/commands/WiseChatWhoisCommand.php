<?php

/**
 * Wise Chat command: /whois [userName]
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
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

        $channelUser = $this->channelUsersDAO->getActiveByUserIdAndChannelId($user->getId(), $this->channel->getId());
        if ($channelUser !== null) {
            $details = sprintf(
                "User: %s \n".
                "ID: %d \n".
                "IP address: %s \n".
                "PHP session ID: %s \n",
                $userName, $user->getId(), $user->getIp(), $user->getSessionId(), $user->get
            );

            $this->addMessage($details);
        } else {
            $this->addMessage(sprintf('User "%s" was not found', $userName));
        }
	}
}