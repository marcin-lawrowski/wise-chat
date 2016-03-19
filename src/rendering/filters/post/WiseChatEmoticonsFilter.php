<?php

/**
 * Wise Chat emoticons filter.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatEmoticonsFilter {
    /**
     * @var array
     */
	private static $emoticons = array(
		'zip-it', 'blush', 'angry', 'not-one-care', 'laugh-big', 'please', 'cool', 'minishock',
		'devil', 'silly', 'smile', 'devil-laugh', 'heart', 'not-guilty', 'hay', 
		'in-love', 'meow', 'tease', 'gift', 'kissy', 'sad', 'speechless', 'goatse', 
		'fools', 'why-thank-you', 'wink', 'angel', 'annoyed', 'flower', 'surprised', 
		'female', 'laugh', 'ill', 'total-shock', 'zzz', 'clock', 'oh', 'mail', 'crazy', 
		'cry', 'boring', 'geek'
	);

    /**
     * @var array
     */
	private static $aliases = array(
		'smile' => array(':)', ':-)'),
		'wink' => array(';)', ';-)'),
		'laugh' => array(':D', ':-D', ':d', ':-d'),
		'laugh-big' => array('xD', 'xd'),
		'sad' => array(':(', ':-('),
		'cry' => array(';(', ';-('),
		'kissy' => array(':*', ':-*'),
		'silly' => array(':P', ':-P', ':p', ':-p'),
		'crazy' => array(';P', ';-P', ';p', ';-p'),
		'angry' => array(':[', ':-['),
		'devil-laugh' => array(':&gt;', ':-&gt;'),
		'devil' => array(':]', ':-]'),
		'goatse' => array(':|', ':-|'),
	);

    /**
     * @var array
     */
    private $replacementArrays;

    /**
     * WiseChatEmoticonsFilter constructor.
     */
    public function __construct() {
        $this->replacementArrays = $this->prepareReplacementArrays();
    }

    /**
	* Detects emoticons and replaces them with images.
	*
	* @param string $text HTML-encoded string
	*
	* @return string
	*/
	public function filter($text) {
		return str_replace($this->replacementArrays[0], $this->replacementArrays[1], $text);
	}
	
	/**
	* Prepares two input parameters for str_replace function.
	*
	* @return array
	*/
	private function prepareReplacementArrays() {
		$searchArray = array();
		$replaceArray = array();
		
		$options = WiseChatOptions::getInstance();
		foreach (self::$emoticons as $emoticon) {
			$filePath = $options->getEmoticonsBaseURL().'/'.$emoticon.'.png';
			$imgTag = sprintf("<img src='%s' alt='%s' class='wcEmoticon' />", $filePath, htmlspecialchars($emoticon, ENT_QUOTES, 'UTF-8'));
			
			$searchArray[] = htmlentities('<'.$emoticon.'>');
			$replaceArray[] = $imgTag;
			
			if (array_key_exists($emoticon, self::$aliases)) {
				foreach (self::$aliases[$emoticon] as $alias) {
					$searchArray[] = $alias;
					$replaceArray[] = $imgTag;
				}
			}
		}
		
		return array($searchArray, $replaceArray);
	}
}