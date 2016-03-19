<?php

/**
 * Wise Chat links post-filter.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatLinksPostFilter {
	const SHORTCODE_REGEXP = '/\[link src=&quot;(.+?)&quot;\]/i';
	const URL_PROTOCOLS_REGEXP = "/^(https|http|ftp)\:\/\//i";
	
	/**
	* Detects all hyperlinks in shortcode format and converts them into real hyperlinks or raw URLs
	*
	* @param string $text HTML-encoded string
	* @param boolean $linksEnabled Whether to convert shortcodes into real hyperlinks
	*
	* @return string
	*/
	public function filter($text, $linksEnabled) {
		if (preg_match_all(self::SHORTCODE_REGEXP, $text, $matches)) {
			if (count($matches) < 2) {
				return $text;
			}
			
			foreach ($matches[0] as $key => $shortCode) {
				$shortCodeSrc = $matches[1][$key];
				
				if ($linksEnabled) {
					$url = $shortCodeSrc;
					if (!preg_match(self::URL_PROTOCOLS_REGEXP, $shortCodeSrc)) {
						$url = "http://".$shortCodeSrc;
					}
					$linkBody = htmlentities(urldecode($shortCodeSrc), ENT_QUOTES, 'UTF-8', false);
					$linkTag = sprintf('<a href="%s" target="_blank" rel="nofollow">%s</a>', $url, $linkBody);
				
					$text = $this->strReplaceFirst($shortCode, $linkTag, $text);
				} else {
					$text = $this->strReplaceFirst($shortCode, $shortCodeSrc, $text);
				}
			}
		}
		
		return $text;
	}

    /**
     * Replaces first occurrence of the needle.
     *
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     *
     * @return string
     */
	private function strReplaceFirst($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		
		if ($pos !== false) {
			return substr_replace($haystack, $replace, $pos, strlen($needle));
		}
		
		return $haystack;
	}
}	