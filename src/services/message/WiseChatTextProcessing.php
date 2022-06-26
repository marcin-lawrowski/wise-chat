<?php

/**
 * WiseChat message text processing utility.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatTextProcessing {

	/**
	 * @param string $text
	 * @param integer $messageMaxLength
	 * @return string
	 */
	public static function cutMessageText($text, $messageMaxLength) {
		if ($messageMaxLength === 0) {
			return $text;
		}

		if (!extension_loaded('mbstring')) {
			return substr($text, 0, $messageMaxLength);
		}

		WiseChatContainer::load('rendering/filters/WiseChatShortcodeConstructor');
		mb_internal_encoding("UTF-8");
		$matches = array();
		$shortcodes = array_map(function($shortcode) { return '('.$shortcode.')'; }, WiseChatShortcodeConstructor::SHORTCODES);

		// extract all shortcodes across all lines with Unicode support:
		preg_match_all('/\[[^\]]+\]/mu', $text, $matches, PREG_OFFSET_CAPTURE);

		$pointer = 0;
		$parts = array();
		foreach ($matches[0] as $match) {
			$shortcode = $match[0];
			$position = mb_strlen(substr($text, 0, $match[1])); // get multibyte-safe position

			// add individual characters:
			$parts = array_merge($parts, preg_split('//u', mb_substr($text, $pointer, $position - $pointer), null, PREG_SPLIT_NO_EMPTY));

			// add the full shortcode if detected:
			if (preg_match('/\['.implode('|', $shortcodes).'[^\]]*\]/', $shortcode)) {
				$parts[] = $shortcode;
			} else {
				$parts = array_merge($parts, preg_split('//u', $shortcode, null, PREG_SPLIT_NO_EMPTY));
			}

			// move the pointer:
			$pointer = $position + mb_strlen($shortcode);
		}

		$parts = array_merge($parts, preg_split('//u', mb_substr($text, $pointer, strlen($text) - $pointer), null, PREG_SPLIT_NO_EMPTY));

		// do not count new lines:
		$limit = 0;
		$processedParts = array();
		foreach ($parts as $part) {
			$processedParts[] = $part;
			if (!in_array($part, array("\n", "\t"))) {
				$limit++;
			}
			if ($limit >= $messageMaxLength) {
				break;
			}
		}

		return implode('', $processedParts);
	}

}