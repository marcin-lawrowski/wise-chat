<?php

/**
 * Wise Chat filters reverse facility.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatPostReversedFilter {

	/**
	 * Reverse HTML tags to internal tags.
	 *
	 * @param string $text HTML string
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function filtersReverse($text) {
		$text = str_replace('&nbsp;', ' ', $text);

		// browsers send <br> tags instead of new line characters:
		$text = str_replace(array('<br>', '<br/>', '<br />'), "\n", $text);
		$text = str_replace('<div>', "<div>\n", $text);

		$text = $this->filterReverseInternal($text);

		// convert entities to characters:
		$text = htmlspecialchars_decode($text);

		return $text;
	}

	/**
	 * Returns number of characters in text parts of the raw HTML message.
	 *
	 * @param string $text
	 * @return int
	 * @throws Exception
	 */
	public function getTextCharactersCount($text) {
		$text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$text.'</body></html>';

		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		if (defined('LIBXML_HTML_NOIMPLIED')) {
			$htmlParseStatus = $html->loadHTML($text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		} else {
			$htmlParseStatus = $html->loadHTML($text);
		}
		if ($htmlParseStatus === false) {
			throw new \Exception('Error parsing input HTML (2)');
		}
		libxml_clear_errors();

		$count = 0;
		$xpath = new DOMXpath($html);
		$elements = $xpath->query('//text()');
		/* @var DomNode $element*/
		foreach ($elements as $element) {
			$count += strlen(trim($element->nodeValue));
		}

		return $count;
	}

	private function filterReverseInternal($text) {
		// the header is required to properly parse unicode characters by loadHTML() method:
		$text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>'.$text.'</body></html>';

		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		if (defined('LIBXML_HTML_NOIMPLIED')) {
			$htmlParseStatus = $html->loadHTML($text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		} else {
			$htmlParseStatus = $html->loadHTML($text);
		}
		if ($htmlParseStatus === false) {
			throw new \Exception('Error parsing input HTML');
		}
		libxml_clear_errors();

		$xpath = new DOMXpath($html);
		$elements = $xpath->query('//*[@data-org]');

		/* @var DOMElement $element*/
		foreach ($elements as $element) {
			if (!($element instanceof DOMElement)) {
				continue;
			}

			// replace element with the internal tag:
			$replacementDone = false;
			$tagEncoded = $element->getAttribute('data-org');
			if ($tagEncoded) {
				$tagDecrypted = base64_decode($tagEncoded);

				if ($tagDecrypted !== false) {
					$textNode = $html->createTextNode(htmlspecialchars_decode($tagDecrypted));
					$element->parentNode->replaceChild($textNode, $element);
					$replacementDone = true;
				} else {
					throw new \Exception('Error decompressing input HTML tag');
				}
			}

			// remove the element if not recognized:
			if (!$replacementDone) {
				$element->parentNode->removeChild($element);
			}
		}

		// output only body:
		$bodyElements = $xpath->query('/html[1]/body[1]');
		if ($bodyElements->length > 0) {
			$output = $html->saveHTML($bodyElements->item(0));
		} else {
			$output = $html->saveHTML();
		}

		return strip_tags($output);
	}

}