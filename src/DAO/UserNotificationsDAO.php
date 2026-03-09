<?php

namespace Kainex\WiseChat\DAO;

use Exception;
use Kainex\WiseChat\Model\UserNotification;
use Kainex\WiseChat\Options;

/**
 * WiseChat user notifications DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserNotificationsDAO {

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var array
	 */
	private $frequencies = array(
		'daily' => 'one a day',
		'hourly' => 'one an hour',
		'minute' => 'one a minute',
		'limitless' => 'no limits'
	);

	/**
	 * @param Options $options
	 */
	public function __construct(Options $options) {
		$this->options = $options;
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
	 * Creates or updates the user notification and returns it.
	 *
	 * @param UserNotification $notification
	 *
	 * @return UserNotification
	 * @throws Exception On validation error
	 */
	public function save($notification) {
		// low-level validation:
		if ($notification->getFrequency() === null) {
			throw new Exception('Frequency is not defined');
		}

		$notifications = (array) $this->options->getOption('user_notifications', array());

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

		$this->options->setOption('user_notifications', $notifications);
		$this->options->saveOptions();

		return $notification;
	}

	/**
	 * Get the notification by ID.
	 *
	 * @param integer $id
	 *
	 * @return UserNotification
	 */
	public function get($id) {
		$notifications = (array) $this->options->getOption('user_notifications', array());

		foreach ($notifications as $key => $notificationSource) {
			if ($notificationSource['id'] == $id) {
				$notification = new UserNotification();
				$notification->setId($notificationSource['id']);
				$notification->setType($notificationSource['type']);
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
	 * @return UserNotification[]
	 */
	public function getAll() {
		$notifications = $this->options->getOption('user_notifications', array());
		if (!is_array($notifications)) {
			return array();
		}

		$list = array();
		foreach ($notifications as $key => $notificationSource) {
			$notification = new UserNotification();
			$notification->setId($notificationSource['id']);
			$notification->setType($notificationSource['type']);
			$notification->setFrequency($notificationSource['frequency']);
			$notification->setDetails($notificationSource['details']);

			$list[] = $notification;
		}

		return $list;
	}

	/**
	 * Deletes the notification by ID.
	 *
	 * @param integer $id
	 *
	 * @return void
	 */
	public function delete($id) {
		$notifications = (array) $this->options->getOption('user_notifications', array());

		foreach ($notifications as $key => $notificationInList) {
			if ($notificationInList['id'] == $id) {
				unset($notifications[$key]);
				break;
			}
		}

		$this->options->setOption('user_notifications', $notifications);
		$this->options->saveOptions();
	}

}