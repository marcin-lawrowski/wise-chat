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
	private static $emoticons_1 = array(
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
	private static $aliases_1 = array(
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
	 * @var string
	 */
	private static $filesExtension_1 = 'png';

	/**
	 * @var array
	 */
	private static $emoticons_2 = array(
		'angry', 'bulb', 'cafe', 'clap', 'clouds', 'cry', 'devil', 'gift', 'handshake',
		'heart', 'kissy', 'laugh-big', 'no', 'ok', 'feel_peace', 'oh_please', 'rain', 'scared',
		'silly', 'snail', 'sun', 'baloons', 'bye', 'cake', 'cleaver', 'cool', 'cry_big',
		'drink', 'hat', 'heart_big', 'laugh', 'moon', 'offended', 'omg', 'a_phone',
		'question', 'sad', 'shy', 'smile', 'stars', 'wine'
	);

	/**
	 * @var array
	 */
	private static $aliases_2 = array(
		'smile' => array(':)', ':-)', ';)', ';-)'),
		'laugh' => array(':D', ':-D', ':d', ':-d'),
		'laugh-big' => array('xD', 'xd'),
		'sad' => array(':(', ':-('),
		'cry' => array(';(', ';-('),
		'kissy' => array(':*', ':-*'),
		'silly' => array(':P', ':-P', ':p', ':-p', ';P', ';-P', ';p', ';-p'),
		'angry' => array(':[', ':-['),
		'devil' => array(':]', ':-]'),
	);

	/**
	 * @var string
	 */
	private static $filesExtension_2 = 'gif';

	/**
	 * @var array
	 */
	private static $emoticons_3 = array(
		'angel', 'confused', 'cthulhu', 'drugged', 'grinning', 'horrified', 'kawaii', 'madness',
		'shy', 'spiteful', 'terrified', 'tongue_out', 'tongue_out_up_left', 'winking_grinning',
		'angry', 'cool', 'cute', 'frowning', 'happy', 'hug', 'kissing', 'malicious', 'sick',
		'stupid', 'thumbs_down', 'tongue_out_laughing', 'unsure', 'winking_tongue_out', 'aww',
		'creepy', 'cute_winking', 'gasping', 'happy_smiling', 'irritated', 'laughing', 'naww',
		'smiling', 'surprised', 'thumbs_up', 'tongue_out_left', 'unsure_2', 'blushing', 'crying',
		'devil', 'greedy', 'heart', 'irritated_2', 'lips_sealed', 'i_am_pouting', 'speechless',
		'surprised_2', 'tired', 'tongue_out_up', 'winking'
	);

	/**
	 * @var array
	 */
	private static $aliases_3 = array(
		'smiling' => array(':)', ':-)'),
		'winking' => array(';)', ';-)'),
		'laughing' => array(':D', ':-D', ':d', ':-d'),
		'madness' => array('xD', 'xd'),
		'frowning' => array(':(', ':-('),
		'crying' => array(';(', ';-('),
		'kissing' => array(':*', ':-*'),
		'tongue_out' => array(':P', ':-P', ':p', ':-p'),
		'winking_tongue_out' => array(';P', ';-P', ';p', ';-p'),
		'angry' => array(':[', ':-['),
		'devil' => array(':&gt;', ':-&gt;'),
		'devil' => array(':]', ':-]'),
		'irritated' => array(':|', ':-|'),
	);

	/**
	 * @var string
	 */
	private static $filesExtension_3 = 'png';

	/**
	 * @var array
	 */
	private static $emoticons_4 = array(
		'angel', 'beer', 'clock', 'crying', 'drink', 'eyeroll', 'glasses-cool', 'jump',
		'mad-tongue', 'sad', 'sick', 'smile-big', 'thinking', 'wilt',
		'angry', 'bomb', 'cloudy', 'cute', 'drool', 'fingers-crossed', 'go-away',
		'kiss', 'mail', 'shock', 'silly', 'smirk', 'tongue', 'wink',
		'arrogant', 'bye', 'coffee', 'devil', 'embarrassed', 'freaked-out', 'good',
		'laugh', 'mean', 'shout', 'sleepy', 'star', 'vampire', 'worship',
		'bad', 'cake', 'confused', 'disapointed', 'excruciating', 'giggle', 'in-love',
		'love', 'neutral', 'rotfl', 'shut-mouth', 'smile', 'struggle', 'weep', 'yawn',
		'beauty', 'hypnotized', 'island', 'quiet', 'rose', 'soccerball'
	);

	/**
	 * @var array
	 */
	private static $aliases_4 = array(
		'smile' => array(':)', ':-)'),
		'wink' => array(';)', ';-)'),
		'laugh' => array(':D', ':-D', ':d', ':-d'),
		'rotfl' => array('xD', 'xd'),
		'sad' => array(':(', ':-('),
		'crying' => array(';(', ';-('),
		'kiss' => array(':*', ':-*'),
		'tongue' => array(':P', ':-P', ':p', ':-p'),
		'silly' => array(';P', ';-P', ';p', ';-p'),
		'angry' => array(':[', ':-['),
		'devil' => array(':&gt;', ':-&gt;'),
		'devil' => array(':]', ':-]'),
		'neutral' => array(':|', ':-|'),
	);

	/**
	 * @var string
	 */
	private static $filesExtension_4 = 'png';

    /**
     * @var array
     */
    private $replacementArrays;

    /**
     * WiseChatEmoticonsFilter constructor.
     */
    public function __construct() {

    }

    /**
	* Detects emoticons and replaces them with images.
	*
	* @param string $text HTML-encoded string
	* @param integer $emoticonsSet Chosen set
	*
	* @return string
	*/
	public function filter($text, $emoticonsSet) {
		if ($this->replacementArrays === null) {
			$this->replacementArrays = $this->prepareReplacementArrays($emoticonsSet);
		}

		// exclude entities:
		$text = preg_replace('/(&[A-Za-z]+;)/', '$1 ', $text);
		$text = str_replace($this->replacementArrays[0], $this->replacementArrays[1], $text);
		$text = preg_replace('/(&[A-Za-z]+;) /', '$1', $text);

		return $text;
	}
	
	/**
	* Prepares two input parameters for str_replace function.
	*
	* @param integer $emoticonsSet
	*
	* @return array
	*/
	private function prepareReplacementArrays($emoticonsSet) {
		$searchArray = array();
		$replaceArray = array();
		$emoticons = self::$emoticons_1;
		$aliases = self::$aliases_1;
		$filesExtension = self::$filesExtension_1;
		if ($emoticonsSet == 2) {
			$emoticons = self::$emoticons_2;
			$aliases = self::$aliases_2;
			$filesExtension = self::$filesExtension_2;
		} else if ($emoticonsSet == 3) {
			$emoticons = self::$emoticons_3;
			$aliases = self::$aliases_3;
			$filesExtension = self::$filesExtension_3;
		} else if ($emoticonsSet == 4) {
			$emoticons = self::$emoticons_4;
			$aliases = self::$aliases_4;
			$filesExtension = self::$filesExtension_4;
		}

		$options = WiseChatOptions::getInstance();
		foreach ($emoticons as $emoticon) {
			$subDirectory = '';
			$extension = '.png';
			if ($emoticonsSet > 1) {
				$subDirectory = '/set_'.$emoticonsSet;
			}
			$filePath = $options->getEmoticonsBaseURL().$subDirectory.'/'.$emoticon.'.'.$filesExtension;
			$imgTag = sprintf("<img src='%s' alt='%s' class='wcEmoticon' />", $filePath, htmlspecialchars($emoticon, ENT_QUOTES, 'UTF-8'));
			
			$searchArray[] = htmlentities('< '.$emoticon.'>');
			$replaceArray[] = $imgTag;
			
			if (array_key_exists($emoticon, $aliases)) {
				foreach ($aliases[$emoticon] as $alias) {
					$searchArray[] = $alias;
					$replaceArray[] = $imgTag;
				}
			}
		}
		
		return array($searchArray, $replaceArray);
	}
}