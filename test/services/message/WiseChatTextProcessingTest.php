<?php

use PHPUnit\Framework\TestCase;

WiseChatContainer::load('services/message/WiseChatTextProcessing');

class WiseChatTextProcessingTest extends TestCase {

	/**
	 * @dataProvider data
	 */
	public function testCut($text, $length, $output) {
		$actualOutput = WiseChatTextProcessing::cutMessageText($text, $length);

		$this->assertEquals($output, $actualOutput);
	}

	public function data() {
		return array(
			array("natural", 0, "natural"),
			array("natural", 2, "na"),
			array("n\na\nt\nu\nr\nal", 5, "n\na\nt\nu\nr"),
			array("kość", 2, "ko"),
			array("kość[link src='test']", 2, "ko"),
			array("śćó", 2, "ść"),
			array("ość", 2, "oś"),
			array("a[link src='test']b", 2, "a[link src='test']"),
			array("a[link src='test']b", 3, "a[link src='test']b"),
			array("a[link src='test']b", 30, "a[link src='test']b"),
			array("a[link]ba[link]b", 4, "a[link]ba"),
			array("a[link]ba[link]b", 5, "a[link]ba[link]"),
			array("a[link]ba[link]b", 6, "a[link]ba[link]b"),
			array("a[link]ba[link]b", 30, "a[link]ba[link]b"),
			array("a[link]ba\n[link]ść[link]", 6, "a[link]ba\n[link]ś"),
			array("a[link]ba\n[link]ś\nć[link]abc", 9, "a[link]ba\n[link]ś\nć[link]a"),
			array("[link]b", 2, "[link]b"),
			array("[link]", 2, "[link]"),
			array("[link]\na", 2, "[link]\na"),
			array("[link]\nab", 2, "[link]\na"),
			array("ść[link]\nab", 4, "ść[link]\na"),
			array("ść[link]re[test]\nab", 8, "ść[link]re[te"),
			array("a[linkb", 3, "a[l"),
		);
	}
}
