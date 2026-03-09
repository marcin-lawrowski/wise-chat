<?php

namespace Kainex\WiseChat\DAO;

use Kainex\WiseChat\Model\Emoticon;
use Kainex\WiseChat\Options;

/**
 * Wise Chat emoticons DAO
 *
 * @author Kainex <contact@kaine.pl>
 */
class EmoticonsDAO {
	/**
	* @var Options
	*/
	private $options;

	/**
	 * @param Options $options
	 */
	public function __construct(Options $options) {
		$this->options = $options;
	}

	/**
	 * Creates or updates the emoticon and returns it.
	 *
	 * @param Emoticon $emoticon
	 *
	 * @return Emoticon
	 * @throws \Exception On validation error
	 */
	public function save($emoticon) {
		// low-level validation:
		if ($emoticon->getAttachmentId() === null) {
			throw new \Exception('Attachment ID cannot equal null');
		}

		// prepare emoticon data:
		$columns = array(
			'attachmentId' => $emoticon->getAttachmentId(),
			'alias' => $emoticon->getAlias(),
		);

		// update or insert:
		$emoticons = (array) $this->options->getOption('emoticons', array());
		if ($emoticon->getId() !== null) {
			foreach ($emoticons as $key => $emoticonEntry) {
				if ($emoticonEntry['id'] == $emoticon->getId()) {
					$emoticons[$key] = array_merge($emoticonEntry, $columns);
					break;
				}
			}
		} else {
			$lastId = $this->options->getIntegerOption("emoticons_last_id", 0);
			$lastId++;
			$columns['id'] = $lastId;
			$emoticons[] = $columns;
			$emoticon->setId($lastId);
			$this->options->setOption('emoticons_last_id', $lastId);
		}

		$this->options->setOption('emoticons', array_values($emoticons));
		$this->options->saveOptions();

		return $emoticon;
	}

	/**
	 * Returns emoticon by ID.
	 *
	 * @param integer $id
	 *
	 * @return Emoticon|null
	 */
	public function get($id) {
		$emoticons = (array) $this->options->getOption('emoticons', array());

		foreach ($emoticons as $key => $emoticonEntry) {
			if ($emoticonEntry['id'] == $id) {
				return $this->populateData($emoticonEntry);
			}
		}

		return null;
	}

	/**
	 * Returns all emoticons.
	 *
	 * @return Emoticon[]
	 */
	public function getAll() {
		$emoticons = (array) $this->options->getOption('emoticons', array());

		$result = array();
		foreach ($emoticons as $emoticon) {
			$result[] = $this->populateData($emoticon);
		}

		return $result;
	}

	/**
	 * Moves an emoticon up by its ID.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function moveUp($id) {
		$index = $this->getEmoticonIndexById($id);
		if ($index !== null && $index > 0) {
			$emoticons = (array) $this->options->getOption('emoticons', array());

			$prev = $emoticons[$index - 1];
			$emoticons[$index - 1] = $emoticons[$index];
			$emoticons[$index] = $prev;

			$this->options->setOption('emoticons', array_values($emoticons));
			$this->options->saveOptions();
		}
	}

	/**
	 * Moves an emoticon down by its ID.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function moveDown($id) {
		$emoticons = (array) $this->options->getOption('emoticons', array());

		$index = $this->getEmoticonIndexById($id);
		if ($index !== null && $index < (count($emoticons) - 1)) {
			$next = $emoticons[$index + 1];
			$emoticons[$index + 1] = $emoticons[$index];
			$emoticons[$index] = $next;

			$this->options->setOption('emoticons', array_values($emoticons));
			$this->options->saveOptions();
		}
	}

	/**
	 * Deletes an emoticon by its ID.
	 *
	 * @param integer $id
	 *
	 * @return null
	 */
	public function delete($id) {
		$index = $this->getEmoticonIndexById($id);
		if ($index !== null) {
			$emoticons = (array) $this->options->getOption('emoticons', array());

			unset($emoticons[$index]);

			$this->options->setOption('emoticons', array_values($emoticons));
			$this->options->saveOptions();
		}
	}

	/**
	 * @param integer $id
	 *
	 * @return int|null
	 */
	private function getEmoticonIndexById($id) {
		$emoticons = (array) $this->options->getOption('emoticons', array());

		foreach ($emoticons as $index => $emoticon) {
			if ($emoticon['id'] == $id) {
				return $index;
			}
		}

		return null;
	}

	/**
	 * Converts a raw object into WiseChatEmoticon object.
	 *
	 * @param \stdClass $rawData
	 *
	 * @return Emoticon
	 */
	private function populateData($rawData) {
		$emoticon = new Emoticon();
		$emoticon->setId($rawData['id']);
		if (intval($rawData['attachmentId']) > 0) {
			$emoticon->setAttachmentId(intval($rawData['attachmentId']));
		}
		$emoticon->setAlias($rawData['alias']);

		return $emoticon;
	}
}