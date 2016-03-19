<?php

/**
 * Wise Chat message shortcodes builders.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatShortcodeConstructor {
	const IMAGE_SHORT_TAG = '[img id="%d" src="%s" src-th="%s" src-org="%s"]';
	const ATTACHMENT_SHORT_TAG = '[attachment id="%d" src="%s" name-org="%s"]';
	const YOUTUBE_SHORT_TAG = '[youtube movie-id="%s" src-org="%s"]';
	
	/**
	* Constructs image shortcode.
	*
	* @param integer $attachmentId
	* @param string $imageSrc
	* @param string $imageThumbnailSrc
	* @param string $originalSrc
	*
	* @return string
	*/
	public static function getImageShortcode($attachmentId, $imageSrc, $imageThumbnailSrc, $originalSrc) {
		return sprintf(self::IMAGE_SHORT_TAG, $attachmentId, $imageSrc, $imageThumbnailSrc, $originalSrc);
	}
	
	/**
	* Constructs attachment shortcode.
	*
	* @param integer $id
	* @param string $source
	* @param string $originalName
	*
	* @return string
	*/
	public static function getAttachmentShortcode($id, $source, $originalName) {
		return sprintf(self::ATTACHMENT_SHORT_TAG, $id, $source, $originalName);
	}
	
	/**
	* Constructs YouTube shortcode.
	*
	* @param string $movieId
	* @param string $originalSrc
	*
	* @return string
	*/
	public static function getYouTubeShortcode($movieId, $originalSrc) {
		return sprintf(self::YOUTUBE_SHORT_TAG, $movieId, $originalSrc);
	}
}	