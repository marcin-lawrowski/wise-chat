<?php

/**
 * WiseChat custom filters DAO. Filter is a regular expression and replacement text.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatFiltersDAO {
	const URL_REGEXP = "((https|http|ftp)\:\/\/)?([\-_a-z0-9A-Z]+\.)+[a-zA-Z]{2,6}(\/[^ \?]*)?(\?[^\"'<> ]+)?";
	const EMAIL_REGEXP = "[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+";
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var array
	*/
	private $types = array(
		'text' => 'Text',
		'link' => 'Hyperlinks',
		'outgoing-link' => 'Outgoing Hyperlinks',
		'email' => 'E-mails',
		'regexp' => 'Regular Expression'
	);
	
	public function __construct() {
		$this->options = WiseChatOptions::getInstance();
	}
	
	/**
	* Returns all filter types.
	*
	* @return array
	*/
	public function getAllTypes() {
		return $this->types;
	}
	
	/**
	* Adds a new filter.
	*
	* @param string $type Type of the filter (a key from $this->types array)
	* @param string $replace
	* @param string $replaceWith
	*
	* @throws Exception If regular expression is invalid
	*/
	public function addFilter($type, $replace, $replaceWith) {
		if ($type === 'regexp' && strlen($replace) > 0 && preg_match("/$replace/", null) === false) {
			throw new Exception('Error while adding the filter - invalid regular expression was detected');
		}
	
		if ($type == 'link') {
			$replace = self::URL_REGEXP;
		} else if ($type == 'outgoing-link') {
			$replace = $this->getOutgoingUrlRegExp();
		} else if ($type == 'email') {
			$replace = self::EMAIL_REGEXP;
		}
	
		$filters = $this->options->getOption('filters', array());
		$filters[] = array(
			'type' => $type,
			'replace' => $replace,
			'with' => $replaceWith
		);
		
		$this->options->setOption('filters', $filters);
		$this->options->saveOptions();
	}
	
	/**
	* Deletes filter by ID.
	*
	* @param integer $id
	*
	* @return void
	*/
	public function deleteById($id) {
		$filters = $this->options->getOption('filters', array());
		if (array_key_exists($id, $filters)) {
			unset($filters[$id]);
		}
		
		$this->options->setOption('filters', $filters);
		$this->options->saveOptions();
	}
	
	/**
	* Returns all filters
	*
	* @param boolean $htmlEscape
	*
	* @return array
	*/
	public function getAll($htmlEscape = false) {
		$filters = $this->options->getOption('filters', array());
		if (!is_array($filters)) {
			return array();
		}
		
		$filtersOut = array();
		foreach ($filters as $key => $filter) {
			$type = $filter['type'];
			$replace = $filter['replace'];
			$with = $filter['with'];
			$label = $this->types[$type].(in_array($type, array('text', 'regexp')) ? ': '.$replace : '');
			
			if ($htmlEscape) {
				$replace = htmlentities($replace, ENT_QUOTES, 'UTF-8');
				$with = htmlentities($with, ENT_QUOTES, 'UTF-8');
				$label = htmlentities($label, ENT_QUOTES, 'UTF-8');
			}
			
			$filtersOut[] = array(
				'id' => $key,
				'replace' => $replace,
				'with' => $with,
				'label' => $label,
				'type' => $type
			);
		}
		
		return $filtersOut;
	}

	private function getOutgoingUrlRegExp() {
		$currentDomain = parse_url(get_site_url(), PHP_URL_HOST);
		$currentDomain = str_replace('.', '\.', $currentDomain);

		return "((https|http|ftp)\:\/\/)?([\-_a-z0-9A-Z]+\.)*{$currentDomain}(\/[^ \?]*)?(\?[^\"'<> ]+)?";
	}
}