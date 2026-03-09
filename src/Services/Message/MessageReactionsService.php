<?php

namespace Kainex\WiseChat\Services\Message;

use Exception;
use Kainex\WiseChat\DAO\Message\MessageReactionDAO;
use Kainex\WiseChat\DAO\Message\MessageReactionLogDAO;
use Kainex\WiseChat\Model\Message\Message;
use Kainex\WiseChat\Model\Message\MessageReaction;
use Kainex\WiseChat\Model\Message\MessageReactionLog;
use Kainex\WiseChat\Services\User\ActionsService;
use Kainex\WiseChat\Services\User\AuthenticationService;
use Kainex\WiseChat\Options;
use Kainex\WiseChat\Services\User\UserFeedService;
use Kainex\WiseChat\Services\User\UserService;

/**
 * WiseChat messages reactions services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class MessageReactionsService {

	/**
	 * @var ActionsService
	 */
	protected $actions;

	/**
	 * @var AuthenticationService
	 */
	private $authentication;

	/**
	 * @var MessageReactionDAO
	 */
	private $reactionDAO;

	/**
	 * @var MessageReactionLogDAO
	 */
	private $reactionLogDAO;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var array
	 */
	private $cacheReactionByMessage = array();

	/**
	 * @var array
	 */
	private $cacheUserReactionsByMessage;

	private UserFeedService $userFeedService;
	private UserService $userService;

	/**
	 * @param ActionsService $actions
	 * @param AuthenticationService $authentication
	 * @param MessageReactionDAO $reactionDAO
	 * @param MessageReactionLogDAO $reactionLogDAO
	 * @param Options $options
	 * @param UserFeedService $userFeedService
	 * @param UserService $userService
	 */
	public function __construct(ActionsService $actions, AuthenticationService $authentication, MessageReactionDAO $reactionDAO, MessageReactionLogDAO $reactionLogDAO, Options $options, \Kainex\WiseChat\Services\User\UserFeedService $userFeedService, \Kainex\WiseChat\Services\User\UserService $userService) {
		$this->actions = $actions;
		$this->authentication = $authentication;
		$this->reactionDAO = $reactionDAO;
		$this->reactionLogDAO = $reactionLogDAO;
		$this->options = $options;
		$this->userFeedService = $userFeedService;
		$this->userService = $userService;
	}

	/**
	 * @return array
	 */
	public function getInitialConfiguration() {
		if ($this->isEnabled()) {
			$reactionsList = array();

			switch ($this->options->getOption('reactions_mode', 'like')) {
				case 'like':
					$reactionsList = array($this->getDefaultReaction('like'));
					break;
			}

			return array(
				'enabled' => true,
				'mode' => $this->options->getOption('reactions_mode', 'like'),
				'group' => $this->options->isOptionEnabled('reactions_buttons_group', false),
				'buttonMode' => $this->options->getOption('reactions_buttons_mode', 'icon_text'),
				'list' => $reactionsList
			);
		}

		return array(
			'enabled' => false
		);
	}

	private function getIconURL($icon) {
		return $this->options->getIconsURL().'reactions/'.$icon;
	}

	private function getDefaultReaction($reactionAlias) {
		switch ($reactionAlias) {
			case 'like':
				$icon = $this->getIconURL('like.svg');
				switch ($this->options->getOption('theme', 'balloon')) {
					case 'colddark':
						$icon = $this->getIconURL('like-light.svg');
						break;
					case 'balloon':
						$icon = $this->getIconURL('like-dark.svg');
						break;
				}

				return array(
					'id' => 1,
					'class' => 'wcReactionLike',
					'action' => __('Like', 'wise-chat'),
					'active' => __('I like it', 'wise-chat'),
					'icon' => $icon,
					'iconSm' => $icon
				);
		}

		return null;
	}

	/**
	 * If reactions features are enabled at all.
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return $this->options->getOption('reactions_mode', 'like') ? true : false;
	}

	/**
	 * @param Message $message
	 * @param integer $reactionId
	 * @throws Exception
	 */
	public function toggleReaction($message, $reactionId) {
		if (!$this->isEnabled()) {
			return;
		}

		if ($reactionId < 1 || $reactionId > 7) {
			throw new \Exception('Unknown reaction');
		}

		$reaction = $this->reactionDAO->getByMessageId($message->getId());
		$reactionLogs = $this->reactionLogDAO->getAllByMessageIdAndUserIdAndReactionId($message->getId(), $this->authentication->getUserIdOrNull(), $reactionId);
		if (!$reaction) {
			$reaction = new MessageReaction();
			$reaction->setMessageId($message->getId());
		}
		$reaction->setUpdated(time());

		$getter = 'getReaction'.$reactionId;
		$setter = 'setReaction'.$reactionId;

		if (count($reactionLogs) > 0) {
			$newValue = $reaction->$getter() - 1;
			$reaction->$setter($newValue >= 0 ? $newValue : 0);

			// remove reaction log:
			$this->reactionLogDAO->deleteAllByMessageIdAndUserIdAndReactionId($message->getId(), $this->authentication->getUserIdOrNull(), $reactionId);

			// remove feed entries:
			if ($message->getUserId() !== $this->authentication->getUserIdOrNull()) {
				$this->userFeedService->deleteByUserIdAndTargetId($message->getUserId(), $message->getId());
			}
		} else {
			$newValue = $reaction->$getter() + 1;
			$reaction->$setter($newValue >= 0 ? $newValue : 0);

			// add reaction log entry:
			$reactionLog = new MessageReactionLog();
			$reactionLog->setMessageId($message->getId());
			$reactionLog->setUserId($this->authentication->getUserIdOrNull());
			$reactionLog->setReactionId($reactionId);
			$reactionLog->setTime(time());
			$this->reactionLogDAO->save($reactionLog);

			// add feed entry:
			if ($message->getUserId() !== $this->authentication->getUserIdOrNull()) {
				$this->userFeedService->create($message->getUserId(), 'message.reaction', $message->getId(), [
					'userId' => $this->authentication->getUserIdOrNull(),
					'reactionId' => $reactionId,
					'channelId' => $message->getChannelId()
				]);
			}
		}
		$this->reactionDAO->save($reaction);
	}

	/**
	 * Reads reactions corresponding to the messages and caches them.
	 *
	 * @param Message[] $messages
	 */
	public function cacheReactions($messages) {
		if (!$this->isEnabled()) {
			return;
		}

		foreach (array_chunk($messages, 200) as $messagesChunk) {
			$ids = array_map(function($message) { return $message->getId(); }, $messagesChunk);
			foreach ($ids as $id) {
				$this->cacheReactionByMessage[$id] = null;
				$this->cacheUserReactionsByMessage[$id] = null;
			}
			$reactions = $this->reactionDAO->getAllByMessageIds($ids);
			foreach ($reactions as $reaction) {
				$this->cacheReactionByMessage[$reaction->getMessageId()] = $reaction;
			}

			$reactionsLogs = $this->reactionLogDAO->getAllByMessageIdsAndUserId($ids, $this->authentication->getUserIdOrNull());
			foreach ($reactionsLogs as $reactionsLog) {
				if (!array_key_exists($reactionsLog->getMessageId(), $this->cacheUserReactionsByMessage)) {
					$this->cacheUserReactionsByMessage[$reactionsLog->getMessageId()] = array();
				}
				$this->cacheUserReactionsByMessage[$reactionsLog->getMessageId()][] = $reactionsLog->getReactionId();
			}
		}
	}

	/**
	 * Reads the reaction of a particular message. The method uses the internal cache.
	 *
	 * @param Message $message
	 * @return MessageReaction|null
	 */
	public function getReactionByMessage($message) {
		if (!$this->isEnabled()) {
			return null;
		}

		if (!array_key_exists($message->getId(), $this->cacheReactionByMessage)) {
			$this->cacheReactions(array($message));
		}

		return $this->cacheReactionByMessage[$message->getId()];
	}

	/**
	 * Reads the reaction of a particular message. The method uses the internal cache.
	 *
	 * @param Message $message
	 * @return MessageReaction|null
	 */
	public function getCurrentUserReactionByMessage($message) {
		if (!$this->isEnabled()) {
			return null;
		}

		if (!array_key_exists($message->getId(), $this->cacheUserReactionsByMessage)) {
			$this->cacheReactions(array($message));
		}

		return $this->cacheUserReactionsByMessage[$message->getId()];
	}

	/**
	 * Reads the reaction of a particular message. The method uses the internal cache.
	 *
	 * @param Message $message
	 * @param bool $includeCounters
	 * @param bool $includeOwn
	 * @return array|null
	 */
	public function getReactionsAsPlainArray($message, $includeCounters = true, $includeOwn = true) {
		if (!$this->isEnabled()) {
			return null;
		}

		$output = array();
		if ($includeCounters) {
			$reaction = $this->getReactionByMessage($message);
			$counters = null;
			if ($reaction) {
				$counters = array(
					1 => $reaction->getReaction1(),
					2 => $reaction->getReaction2(),
					3 => $reaction->getReaction3(),
					4 => $reaction->getReaction4(),
					5 => $reaction->getReaction5(),
					6 => $reaction->getReaction6(),
					7 => $reaction->getReaction7(),
				);
				$counters = array_filter($counters);
			}

			$output['counters'] = $counters;
		}
		if ($includeOwn) {
			$output['own'] = $this->getCurrentUserReactionByMessage($message);
		}

		return $output;
	}

	public function deleteAll() {
		$this->reactionDAO->deleteAll();
		$this->reactionLogDAO->deleteAll();
	}

	public function getReactionsLog(Message $message): array {
		$reactionsMap = [];
		$reactionsConfiguration = $this->getInitialConfiguration();
		if ($reactionsConfiguration['enabled'] === true) {
			foreach ($reactionsConfiguration['list'] as $reaction) {
				$reactionsMap[$reaction['id']] = $reaction;
			}
		} else {
			return [];
		}

		$log = $this->reactionLogDAO->getAllByMessageId($message->getId());
		$items = [];
		foreach ($log as $logItem) {
			$items[] = [
				'reaction' => $reactionsMap[$logItem->getReactionId()] ?? null,
				'user' => [
					'id' => $logItem->getUser()->getId(),
					'name' => $logItem->getUser()->getName(),
					'avatarUrl' => $this->userService->getUserAvatar($logItem->getUser())
				]
			];
		}

		return $items;
	}

}