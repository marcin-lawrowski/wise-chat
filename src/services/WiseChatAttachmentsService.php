<?php

/**
 * Wise Chat attachments services class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatAttachmentsService {
	const UPLOAD_FILE_NAME = '_wise_chat_upload_attachment';
	
	/**
	* @var string
	*/
	private $tempFileName;
	
	/**
	* @var array
	*/
	private $logs;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var array
	*/
	private $securityExcludedFormats = array('php', 'php4', 'php5', 'php3', 'inc');
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
		$this->logs = array();
	}
	
	/**
	* Saves given attachment into the Media Library.
	* Returns file details as an array or null if an error occurred.
	*
	* @param string $name Name of the file
	* @param string $data Raw file data
	* @param string $channel Channel
	*
	* @return array|null Array containing keys: id and file
	*/
	public function saveAttachment($name, $data, $channel) {
		if (!$this->checkRequirements()) {
			return null;
		}
		
		$result = null;
		$this->createTempFile();
		$this->saveTempFile($data);
		if (is_array($this->getTempFileImageInfo($name))) {
			$result = $this->saveInMediaLibrary($name, $channel);
		}
		
		$this->deleteTempFile();
		
		return $result;
	}
	
	/**
	* Returns list of allowed attachment formats. 
	*
	* @return array
	*/
	public function getAllowedFormats() {
		$validFormats = array();
		
		if ($this->options->isOptionEnabled('enable_attachments_uploader')) {
			$formats = $this->options->getEncodedOption('attachments_file_formats');
			$formatsSplited = preg_split('/,/', $formats);
			
			if (is_array($formatsSplited)) {
				foreach ($formatsSplited as $format) {
					$proposedFormat = strtolower(trim($format));
					if (!in_array($proposedFormat, $this->securityExcludedFormats)) {
						$validFormats[] = $proposedFormat;
					}
				}
			}
		}
		
		return $validFormats;
	}
	
	/**
	* Returns list of allowed file extensions.
	*/
	public function getAllowedExtensionsList() {
		$formats = $this->getAllowedFormats();
		$prepared = array();
		foreach ($formats as $format) {
			$prepared[] = '.'.$format;
		}
		
		return implode(',', $prepared);
	}
	
	/**
	* Returns maximum size of an attachment file. 
	*
	* @return integer
	*/
	public function getSizeLimit() {
		return $this->options->getIntegerOption('attachments_size_limit', 3145728);
	}
	
	/**
	* Marks attachments with channel, message ID and the plugni signature.
	*
	* @param array $attachmentIds
	* @param string $channel
	* @param integer $messageId
	*/
	public function markAttachmentsWithDetails($attachmentIds, $channel, $messageId) {
		foreach ($attachmentIds as $attachmentId) {
			add_post_meta($attachmentId, 'wise_chat_message_id', $messageId);
			add_post_meta($attachmentId, 'wise_chat_channel', $channel);
			add_post_meta($attachmentId, 'wise_chat', '1');
		}
	}
	
	/**
	* Finds and returns attachments by IDs of messages. 
	*
	* @param array $messagesIds
	*
	* @return array Array of attachments objects
	*/
	public function getAttachmentsByMessageIds($messagesIds) {
		$args = array(
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'wise_chat_message_id',
					'value' => $messagesIds,
					'compare' => 'IN'
				)
			)
		);
		$query = new WP_Query($args);
		
		return $query->get_posts();
	}
	
	/**
	* Get all attachments saved in the Media Library for given channel.
	*
	* @param string $channel
	*
	* @return array Array of attachments objects
	*/
	public function getAttachmentsByChannel($channel) {
		$args = array(
			'meta_key' => 'wise_chat_channel',
			'meta_value' => $channel,
			'post_type' => 'attachment',
			'post_status' => 'any',
			'posts_per_page' => -1
		);
		
		return get_posts($args);
	}
	
	/**
	* Get all attachments saved in the Media Library by the plugin.
	*
	* @return array Array of attachments objects
	*/
	public function getAllAttachments() {
		$args = array(
			'meta_key' => 'wise_chat',
			'meta_value' => '1',
			'post_type' => 'attachment',
			'post_status' => 'any',
			'posts_per_page' => -1
		);
		
		return get_posts($args);
	}
	
	/**
	* Deletes attachments by IDs of messages. 
	*
	* @param array $messagesIds
	*/
	public function deleteAttachmentsByMessageIds($messagesIds) {
		$this->deleteAttachemnts($this->getAttachmentsByMessageIds($messagesIds));
	}
	
	/**
	* Deletes attachments by the channel's name.
	*
	* @param string $channel
	*/
	public function deleteAttachmentsByChannel($channel) {
		$this->deleteAttachemnts($this->getAttachmentsByChannel($channel));
	}
	
	/**
	* Deletes all attachments saved by the plugin.
	*/
	public function deleteAllAttachments() {
		$this->deleteAttachemnts($this->getAllAttachments());
	}
	
	/**
	* Deletes attachments.
	*
	* @param array $attachments
	*/
	private function deleteAttachemnts($attachments) {
		foreach ($attachments as $attachment) {
			$meta = get_post_meta(intval($attachment->ID), 'wise_chat');
			if (is_array($meta) && count($meta) > 0) {
				wp_delete_attachment(intval($attachment->ID), true);
			}
		}
	}
	
	/**
	* Saves file passed in $_FILES (under self::UPLOAD_FILE_NAME key) in the Media Library.
	* Returns null if error occurrs.
	*
	* @param string $channel Channel
	*
	* @return array|null
	*/
	private function saveInMediaLibrary($name, $channel) {
		$result = null;
		
		require_once(ABSPATH.'wp-admin/includes/image.php');
		require_once(ABSPATH.'wp-admin/includes/file.php');
		require_once(ABSPATH.'wp-admin/includes/media.php');
		
		$attachmentId = media_handle_sideload($_FILES[self::UPLOAD_FILE_NAME], 0, null, array());
		if (is_wp_error($attachmentId)) {
			$this->logError('Error creating new entry in media library: '.$attachmentId->get_error_message());
		} else {
			$result = array(
				'id' => $attachmentId,
				'file' => wp_get_attachment_url($attachmentId)
			);
			
			$postUpdate = array(
				'ID' => $attachmentId,
				'post_title' => $name
			);
			wp_update_post($postUpdate);
		}
		
		return $result;
	}
	
	/**
	* Returns information about the temporary file but only if it is an image file.
	*
	* @param string $fileName Name of the file
	*
	* @return null|array
	*/
	private function getTempFileImageInfo($fileName) {
		if (file_exists($this->tempFileName)) {
			$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
			$allowedFormats = $this->getAllowedFormats();
			if (!in_array($extension, $allowedFormats)) {
				$this->logError('Unsupported file type: '.$extension);
				return null;
			}
			
			$fileSize = filesize($this->tempFileName);
			if ($fileSize > $this->options->getIntegerOption('attachments_size_limit', 3145728)) {
				$this->logError('Attachment is to big: '.$fileSize.' bytes');
				return null;
			}
			
			$fileName = date('Ymd-His').'-'.uniqid(rand()).'.'.$extension;
			
			return $_FILES[self::UPLOAD_FILE_NAME] = array(
				'name' => $fileName,
				'type' => 'application/octet-stream',
				'tmp_name' => $this->tempFileName,
				'error' => 0,
				'size' => $fileSize,
			);
		}
		
		$this->logError('The file does not exist');
		
		return null;
	}
	
	/**
	* Checks requirements of images processing.
	*
	* @return boolean
	*/
	private function checkRequirements() {
		if (!extension_loaded('gd') || !function_exists('gd_info')) {
			$this->logError('GD extension is not installed');
			return false;
		}
		if (!extension_loaded('curl') || !function_exists('curl_init')) {
			$this->logError('Curl extension is not installed');
			return false;
		}
		
		return true;
	}
	
	/**
	* Adds error log to the list of logs.
	*
	* @return null
	*/
	private function logError($message) {
		trigger_error('WordPress Wise Chat plugin error: '.$message, E_USER_NOTICE);
		$this->logs[] = 'Error: '.$message;
	}
	
	/**
	* Saves given data in the temporary file.
	*
	* @param string $data
	*
	* @return null
	*/
	private function saveTempFile($data) {
		$fp = fopen($this->tempFileName,'w');
		fwrite($fp, $data);
		fclose($fp);
	}
	
	/**
	* Creates a temporary file in /tmp directory.
	*
	* @return null
	*/
	private function createTempFile() {
		$this->deleteTempFile();
		$this->tempFileName = tempnam('/tmp', 'php_files');
	}
	
	/**
	* Removes the temporary file which was created by the $this->createTempFile() method.
	*
	* @return null
	*/
	private function deleteTempFile() {
		if (strlen($this->tempFileName) > 0 && file_exists($this->tempFileName) && is_writable($this->tempFileName)){
			unlink($this->tempFileName);
		}
	}
	
}