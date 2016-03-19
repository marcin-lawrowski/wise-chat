<?php

/**
 * Wise Chat YouTube videos post-filter.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatYouTubePostFilter {
	const SHORTCODE_REGEXP = '/\[youtube movie-id=&quot;(.+?)&quot; src-org=&quot;(.+?)&quot;\]/i';
	const URL_PROTOCOLS_REGEXP = "/^(https|http)\:\/\//i";
    const YOU_TUBE_IFRAME_TEMPLATE = '<iframe width="%d" height="%d" class="wcVideoPlayer" src="https://www.youtube.com/embed/%s" frameborder="0" allowfullscreen></iframe>';
	
	/**
	* Detects all youtube movies in shortcode format and converts them into video players, clickable URLs or raw URLs
	*
	* @param string $text HTML-encoded string
	* @param boolean $youtubeEnabled Whether to convert shortcodes into video players
	* @param boolean $linksEnabled Whether to convert shortcodes into real hyperlinks
	* @param integer $playerWidth
	* @param integer $playerHeight
	*
	* @return string
	*/
	public function filter($text, $youtubeEnabled, $linksEnabled, $playerWidth, $playerHeight) {
		if (preg_match_all(self::SHORTCODE_REGEXP, $text, $matches)) {
			if (count($matches) < 3) {
				return $text;
			}
			
			foreach ($matches[0] as $key => $shortCode) {
				$movieId = $matches[1][$key];
				$srcOrg = $matches[2][$key];
				$replace = '';
			
				if ($youtubeEnabled && strlen($movieId) > 0) {
					$replace = sprintf(self::YOU_TUBE_IFRAME_TEMPLATE, $playerWidth, $playerHeight, $movieId);
				} else if ($linksEnabled && strlen($srcOrg) > 0) {
					$url = (!preg_match(self::URL_PROTOCOLS_REGEXP, $srcOrg) ? 'http://' : '').$srcOrg;
					$linkBody = htmlentities(urldecode($srcOrg), ENT_QUOTES, 'UTF-8', false);
					$replace = sprintf('<a href="%s" target="_blank" rel="nofollow">%s</a>', $url, $linkBody);
				} else if (strlen($srcOrg) > 0) {
                    $replace = $srcOrg;
                }
				
				$text = $this->strReplaceFirst($shortCode, $replace, $text);
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