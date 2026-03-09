<?php

namespace Kainex\WiseChat\Services;

use Kainex\WiseChat\DAO\FiltersDAO;

/**
 * Wise Chat text filtering service.
 *
 * @author Kainex <contact@kaine.pl>
 */
class FilterChain {

	/** @var FiltersDAO */
	private $filtersDAO;

	/**
	 * @param FiltersDAO $filtersDAO
	 */
	public function __construct(FiltersDAO $filtersDAO) {
		$this->filtersDAO = $filtersDAO;
	}

	/**
     * Method loads all user-defined filters and applies them to the given text.
     *
     * @param string $text A text to filter
     * @return string
     */
	public function filter($text) {
        $filtersChain = $this->filtersDAO->getAll();
		
		foreach ($filtersChain as $filter) {
			$type = $filter['type'];
			$replace = $filter['replace'];
			$replaceWith = $filter['with'];
			
			if ($type == 'text') {
				$text = str_replace($replace, $replaceWith, $text);
			} else if ($type == 'outgoing-link') {
				$matches = array();
				$replaceSource = '/'.FiltersDAO::URL_REGEXP.'/i';
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