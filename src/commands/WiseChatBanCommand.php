<?php

/**
 * WiseChat command: /ban [userName] [duration]
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatBanCommand extends WiseChatAbstractCommand {
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
        if ($channelUser === null) {
            $this->addMessage('User was not found');
            return;
        }

        $duration = $this->bansService->getDurationFromString($this->arguments[1]);
        if ($this->bansService->banIpAddress($user->getIp(), $duration)) {
            $this->addMessage("IP " . $user->getIp() . " has been banned, time: {$duration} seconds");
        } else {
            $this->addMessage("IP " . $user->getIp() . " is already banned");
        }
	}
}