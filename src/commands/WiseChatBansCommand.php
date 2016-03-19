<?php

/**
 * WiseChat command: /bans
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatBansCommand extends WiseChatAbstractCommand {
	public function execute() {
		$currentBans = $this->bansDAO->getAll();
		
		if (is_array($currentBans) && count($currentBans) > 0) {
			$bans = array();
			foreach ($currentBans as $ban) {
				$eta = $ban->getTime() - time();
				if ($eta > 0) {
					$bans[] = $ban->getIp().' ('.$this->getTimeSummary($eta).')';
				}
			}
			
			$this->addMessage('Currently banned IPs and remaining time: '.(count($bans) > 0 ? implode(', ', $bans) : ' empty list'));
		} else {
			$this->addMessage('No bans have been added yet');
		}
	}
	
	private function getTimeSummary($seconds) {
		$dateFirst = new DateTime("@0");
		$dateSecond = new DateTime("@$seconds");
		
		return $dateFirst->diff($dateSecond)->format('%a days, %h hours, %i minutes and %s seconds');
	}
}