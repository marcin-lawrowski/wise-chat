<?php

/**
 * WiseChat command: /unban
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatUnbanCommand extends WiseChatAbstractCommand {
	public function execute() {
		$ip = isset($this->arguments[0]) ? $this->arguments[0] : null;
		if ($ip === null) {
            $this->addMessage('Please specify the IP address');
            return;
        }

        $ban = $this->bansDAO->getByIp($ip);
        if ($ban !== null) {
            $this->bansDAO->deleteByIp($ban->getIp());
            $this->addMessage("Ban on IP address ".$ban->getIp()." has been removed");
        } else {
            $this->addMessage('This IP address has not been banned');
        }
	}
}