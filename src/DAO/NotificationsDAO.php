<?php

namespace Kainex\WiseChat\DAO;

use Kainex\WiseChat\Model\Notification;
use Kainex\WiseChat\Options;

/**
 * WiseChat notifications DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class NotificationsDAO {

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var array
	 */
	private $actions = array(
		'message' => 'a message is posted',
		'message-of-user' => 'a message of user is posted',
	);

	/**
	 * @var array
	 */
	private $frequencies = array(
		'daily' => 'one a day',
		'hourly' => 'one an hour',
		'minute' => 'one a minute',
		'limitless' => 'no limits',
	);

	/**
	 * @param Options $options
	 */
	public function __construct(Options $options) {
		$this->options = $options;
	}

	/**
	 * Returns all actions.
	 *
	 * @return array
	 */
	public function getAllActions() {
		return $this->actions;
	}

	/**
	 * Returns all frequencies.
	 *
	 * @return array
	 */
	public function getAllFrequencies() {
		return $this->frequencies;
	}

	/**
	 * Creates or updates the notification and returns it.
	 *
	 * @param Notification $notification
	 *
	 * @return Notification
	 * @throws \Exception On validation error
	 */
	public function save($notification) {
		// low-level validation:
		if ($notification->getAction() === null) {
			throw new \Exception('Action is not defined');
		}
		if ($notification->getFrequency() === null) {
			throw new \Exception('Frequency is not defined');
		}

		$notifications = (array) $this->options->getOption('notifications', array());

		// update or insert:
		if ($notification->getId() !== null) {
			foreach ($notifications as $key => $notificationInList) {
				if ($notificationInList['id'] == $notification->getId()) {
					$notifications[$key] = $notification->asArray();
					break;
				}
			}
		} else {
			$notification->setId(sha1(uniqid().time()));
			$notifications[] = $notification->asArray();
		}

		$this->options->setOption('notifications', $notifications);
		$this->options->saveOptions();

		return $notification;
	}

	/**
	 * Get notification by ID.
	 *
	 * @param integer $id
	 *
	 * @return Notification
	 */
	public function get($id) {
		$notifications = (array) $this->options->getOption('notifications', array());

		foreach ($notifications as $key => $notificationSource) {
			if ($notificationSource['id'] == $id) {
				$notification = new Notification();
				$notification->setId($notificationSource['id']);
				$notification->setType($notificationSource['type']);
				$notification->setAction($notificationSource['action']);
				$notification->setFrequency($notificationSource['frequency']);
				$notification->setDetails($notificationSource['details']);

				return $notification;
			}
		}

		return null;
	}

	/**
	 * Returns all notifications
	 *
	 * @return Notification[]
	 */
	public function getAll() {
		$notifications = $this->options->getOption('notifications', array());
		if (!is_array($notifications)) {
			return array();
		}

		$list = array();
		foreach ($notifications as $key => $notificationSource) {
			$notification = new Notification();
			$notification->setId($notificationSource['id']);
			$notification->setType($notificationSource['type']);
			$notification->setAction($notificationSource['action']);
			$notification->setFrequency($notificationSource['frequency']);
			$notification->setDetails($notificationSource['details']);

			$list[] = $notification;
		}

		return $list;
	}

	/**
	 * Deletes notification by ID.
	 *
	 * @param integer $id
	 *
	 * @return void
	 */
	public function delete($id) {
		$notifications = (array) $this->options->getOption('notifications', array());

		foreach ($notifications as $key => $notificationInList) {
			if ($notificationInList['id'] == $id) {
				unset($notifications[$key]);
				break;
			}
		}

		$this->options->setOption('notifications', $notifications);
		$this->options->saveOptions();
	}

}