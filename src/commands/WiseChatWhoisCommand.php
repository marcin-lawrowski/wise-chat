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
            $this->addMessage('User was not found');
            return;
        }

        $channelUser = $this->channelUsersDAO->getActiveByUserIdAndChannelId($user->getId(), $this->channel->getId());
        if ($channelUser !== null) {
            $details = sprintf("User: %s, IP: %s", $userName, $user->getIp());

            $this->addMessage($details);
        } else {
            $this->addMessage('User was not found');
        }
	}
}