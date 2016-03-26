<?php

/**
 * Wise Chat text filtering service.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatFilterChain {
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}

    /**
     * Method loads all user-defined filters and applies them to the given text.
     *
     * @param string $text A text to filter
     * @return string
     */
	public function filter($text) {
        $filtersChain = WiseChatContainer::get('dao/WiseChatFiltersDAO')->getAll();
		
		foreach ($filtersChain as $filter) {
			$type = $filter['type'];
			$replace = $filter['replace'];
			$replaceWith = $filter['with'];
			
			if ($type == 'text') {
				$text = str_replace($replace, $replaceWith, $text);
			} else if ($type == 'outgoing-link') {
				$matches = array();
				$replaceSource = '/'.WiseChatFiltersDAO::URL_REGEXP.'/i';
				if (preg_match_all($replaceSource, $text, $matches)) {
					foreach ($matches[0] as $value) {
						if (!preg_match('/'.$replace.'/i', $value)) {
							$text = self::strReplaceFirst($value, $replaceWith, $text);
						}
					}
				}
			} else {
				$matches = array();
				$replace = '/'.$replace.'/i';
				if (preg_match_all($replace, $text, $matches)) {
					foreach ($matches[0] as $value) {
						$text = self::strReplaceFirst($value, $replaceWith, $text);
					}
				}
			}
		}
		
		return $text;
	}
	
	private static function strReplaceFirst($needle, $replace, $haystack) {
		$pos = strpos($haystack, $needle);
		
		if ($pos !== false) {
			return substr_replace($haystack, $replace, $pos, strlen($needle));
		}
		
		return $haystack;
	}
}