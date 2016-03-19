<?php

/**
 * Wise Chat bad words filter.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatFilter {
	const BAD_WORDS_FILE = '/../../../../data/bad_words.txt';
	public static $words = null;
	
	public static function filter($text, $replacementText = null) {
		if (self::$words === null) {
			self::$words = file(dirname(__FILE__).self::BAD_WORDS_FILE, FILE_IGNORE_NEW_LINES);
		}
		
		if (extension_loaded('mbstring')) {
			mb_internal_encoding("UTF-8");
			
			foreach (self::$words as $word) {
				$notFuzzyMatching = true;
				$word = trim($word);
				
				// detect empty lines and comments:
				if (self::isCommentOrEmpty($word)) {
					continue;
				}
				
				// fuzzy matching:
				if (self::isFuzzyMatchingMb($word)) {
					$notFuzzyMatching = false;
					$word = trim($word, '[]');
				}
				
				// masked word:
				$masked = self::getMaskedWordMb($word, $replacementText);
				
				// compose regexp for the word:
				$regexp = mb_substr($word, 0, 1);
				for ($i = 1; $i < mb_strlen($word); $i++) {
					$regexp .= "[^\p{L}]*";
					$regexp .= preg_quote(mb_substr($word, $i, 1));
				}
				
				// if not fuzzy matching:
				if ($notFuzzyMatching) {
					$regexp = "(^|[^\p{L}])".$regexp."([^\p{L}]|$)";
					$masked = '$1'.$masked.'$2';
				}
				
				$text = preg_replace("/{$regexp}/ui", $masked, $text);
			}
		} else {
			foreach (self::$words as $word) {
				$notFuzzyMatching = true;
				$word = trim($word);
				
				// detect empty lines and comments:
				if (self::isCommentOrEmpty($word)) {
					continue;
				}
				
				// fuzzy matching:
				if (self::isFuzzyMatching($word)) {
					$notFuzzyMatching = false;
					$word = trim($word, '[]');
				}
				
				// masked word:
				$masked = $replacementText !== null ? $replacementText : self::getMaskedWord($word);
				
				// compose regexp for the word:
				$regexp = substr($word, 0, 1);
				for ($i = 1; $i < strlen($word); $i++) {
					$regexp .= "[^\p{L}]*";
					$regexp .= preg_quote(substr($word, $i, 1));
				}
				
				// if not fuzzy matching:
				if ($notFuzzyMatching) {
					$regexp = "(^|[^\p{L}])".$regexp."([^\p{L}]|$)";
					$masked = '$1'.$masked.'$2';
				}
				
				$text = preg_replace("/{$regexp}/i", $masked, $text);
			}
		}
		
		return $text;
	}
	
	private static function isCommentOrEmpty($word) {
		return strlen($word) === 0 || strpos($word, '#') === 0;
	}
	
	private static function isFuzzyMatching($word) {
		return substr($word, 0, 1) == '[' && substr($word, strlen($word) - 1, 1) == ']';
	}
	
	private static function isFuzzyMatchingMb($word) {
		return mb_substr($word, 0, 1) == '[' && mb_substr($word, mb_strlen($word) - 1, 1) == ']';
	}
	
	private static function getMaskedWord($word) {
		return substr($word, 0, 1).str_repeat('*', strlen($word) - 2).substr($word, strlen($word) - 1);
	}
	
	private static function getMaskedWordMb($word, $replacementText = null) {
        $replacement = $replacementText !== null ? $replacementText : str_repeat('*', mb_strlen($word) - 2);

		return mb_substr($word, 0, 1).$replacement.mb_substr($word, mb_strlen($word) - 1);
	}
}