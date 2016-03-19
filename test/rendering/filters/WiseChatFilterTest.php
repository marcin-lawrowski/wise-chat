<?php

WiseChatContainer::load('rendering/filters/pre/WiseChatFilter');

class WiseChatFilterTest extends PHPUnit_Framework_TestCase {
	private static $mbExtension = 'mbstring';
	
	/**
	 * @dataProvider data
	 */
    public function testPositive($input, $output) {
		WiseChatFilter::$words = array(
			'balls'
		);
		if (!extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    /**
	 * @dataProvider dataNegative
	 */
    public function testNegative($input, $output) {
		WiseChatFilter::$words = array(
			'balls'
		);
		if (!extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    /**
	 * @dataProvider dataUnicode
	 */
    public function testPositiveUnicode($input, $output) {
		WiseChatFilter::$words = array(
			'balls', 'kość'
		);
		if (extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    /**
	 * @dataProvider dataNegativeUnicode
	 */
    public function testNegativeUnicode($input, $output) {
		WiseChatFilter::$words = array(
			'balls', 'kość'
		);
		if (extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    public function data() {
		return array(
			array("balls", "b***s"),
			array("balls,", "b***s,"),
			array("balls?", "b***s?"),
			array("balls*", "b***s*"),
			array("balls.", "b***s."),
			array("balls#", "b***s#"),
			array("balls$", "b***s$"),
			array("balls??", "b***s??"),
			array("!!balls", "!!b***s"),
			array(".balls", ".b***s"),
			array("\balls", "\b***s"),
			array("Balls", "b***s"),
			array("BALLS", "b***s"),
			array("balLS", "b***s"),
			array("these balls", "these b***s"),
			array("these balls,", "these b***s,"),
			array("these balls,", "these b***s,"),
			array("these BalLS,", "these b***s,"),
			array("these b.alls,", "these b***s,"),
			array("these b.a.ll..s,", "these b***s,"),
			array("these b.a.ll.0.s,", "these b***s,"),
			array("these b.aLL-s", "these b***s"),
			array("these B-a-L-L-s!!", "these b***s!!")
		);
    }
    
    public function dataUnicode() {
		return array(
			array("balls", "b***s"),
			array("balls,", "b***s,"),
			array("balls?", "b***s?"),
			array("balls*", "b***s*"),
			array("balls.", "b***s."),
			array("balls#", "b***s#"),
			array("balls$", "b***s$"),
			array("balls??", "b***s??"),
			array("!!balls", "!!b***s"),
			array(".balls", ".b***s"),
			array("\balls", "\b***s"),
			array("Balls", "b***s"),
			array("BALLS", "b***s"),
			array("balLS", "b***s"),
			array("these balls", "these b***s"),
			array("these balls,", "these b***s,"),
			array("these balls,", "these b***s,"),
			array("these BalLS,", "these b***s,"),
			array("these b.alls,", "these b***s,"),
			array("these b.a.ll..s,", "these b***s,"),
			array("these b.a.ll.0.s,", "these b***s,"),
			array("these b.aLL-s", "these b***s"),
			array("these B-a-L-L-s!!", "these b***s!!"),
			
			array("kość", "k**ć"),
			array("kość,", "k**ć,"),
			array("kość?", "k**ć?"),
			array("kość??", "k**ć??"),
			array("kość*", "k**ć*"),
			array("kość.", "k**ć."),
			array("!!kość", "!!k**ć"),
			array(".kość", ".k**ć"),
			array("\kość", "\k**ć"),
			array("Kość", "k**ć"),
			array("KoŚć", "k**ć"),
			array("KOŚĆ", "k**ć"),
			array("the kość", "the k**ć"),
			array("k.o.ś.ć", "k**ć"),
			array("k--o-ś/ć", "k**ć"),
			array("ko8ś0ć", "k**ć"),
			array("--*ko8ś0ć--", "--*k**ć--"),
			array("the ko8ś0ć", "the k**ć"),
			array("ko8Ś0ć!!", "k**ć!!"),
		);
    }
    
    public function dataNegative() {
		return array(
			array("yourballs", "yourballs"),
			array("ballss", "ballss"),
			array("1alls", "1alls"),
			array("baLLss", "baLLss"),
			array("ballls", "ballls"),
		);
	}
	
	public function dataNegativeUnicode() {
		return array(
			array("yourballs", "yourballs"),
			array("ballss", "ballss"),
			array("1alls", "1alls"),
			array("baLLss", "baLLss"),
			array("ballls", "ballls"),
			
			array("kiść", "kiść"),
			array("kośći", "kośći"),
			array("ikość", "ikość"),
			array("kśość", "kśość"),
			array("kośćć", "kośćć"),
		);
	}
	
	/**
	 * @dataProvider dataFuzzyPositive
	 */
    public function testFuzzyPositive($input, $output) {
		WiseChatFilter::$words = array(
			'[balls]'
		);
		if (!extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    /**
	 * @dataProvider dataFuzzyNegative
	 */
    public function testFuzzyNegative($input, $output) {
		WiseChatFilter::$words = array(
			'[balls]'
		);
		if (!extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    /**
	 * @dataProvider dataFuzzyPositiveUnicode
	 */
    public function testFuzzyPositiveUnicode($input, $output) {
		WiseChatFilter::$words = array(
			'[balls]', '[kość]'
		);
		if (extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    /**
	 * @dataProvider dataFuzzyNegativeUnicode
	 */
    public function testFuzzyNegativeUnicode($input, $output) {
		WiseChatFilter::$words = array(
			'[balls]', '[kość]'
		);
		if (extension_loaded(self::$mbExtension)) {
			$this->assertEquals($output, WiseChatFilter::filter($input));
		}
    }
    
    public function dataFuzzyPositive() {
		return array(
			array("balls", "b***s"),
			array("balls,", "b***s,"),
			array("balls?", "b***s?"),
			array("balls*", "b***s*"),
			array("balls.", "b***s."),
			array("balls#", "b***s#"),
			array("balls$", "b***s$"),
			array("balls??", "b***s??"),
			array("!!balls", "!!b***s"),
			array(".balls", ".b***s"),
			array("\balls", "\b***s"),
			array("Balls", "b***s"),
			array("BALLS", "b***s"),
			array("balLS", "b***s"),
			array("these balls", "these b***s"),
			array("these balls,", "these b***s,"),
			array("these balls,", "these b***s,"),
			array("these BalLS,", "these b***s,"),
			array("these b.alls,", "these b***s,"),
			array("these b.a.ll..s,", "these b***s,"),
			array("these b.a__ll__s,", "these b***s,"),
			array("these b.a.ll.0.s,", "these b***s,"),
			array("these b.aLL-s", "these b***s"),
			array("these B-a-L-L-s!!", "these b***s!!"),
			array("yourballs", "yourb***s"),
			array("youRBalls", "youRb***s"),
			array("ballsof yours", "b***sof yours"),
			array("baLLss", "b***ss")
		);
    }
    
    public function dataFuzzyNegative() {
		return array(
			array("balllss", "balllss"),
			array("1alls", "1alls")
		);
	}
	
	public function dataFuzzyPositiveUnicode() {
		return array(
			array("balls", "b***s"),
			array("balls,", "b***s,"),
			array("balls?", "b***s?"),
			array("balls*", "b***s*"),
			array("balls.", "b***s."),
			array("balls#", "b***s#"),
			array("balls$", "b***s$"),
			array("balls??", "b***s??"),
			array("!!balls", "!!b***s"),
			array(".balls", ".b***s"),
			array("\balls", "\b***s"),
			array("Balls", "b***s"),
			array("BALLS", "b***s"),
			array("balLS", "b***s"),
			array("these balls", "these b***s"),
			array("these balls,", "these b***s,"),
			array("these balls,", "these b***s,"),
			array("these BalLS,", "these b***s,"),
			array("these b.alls,", "these b***s,"),
			array("these b.a.ll..s,", "these b***s,"),
			array("these b.a__ll__s,", "these b***s,"),
			array("these b.a.ll.0.s,", "these b***s,"),
			array("these b.aLL-s", "these b***s"),
			array("these B-a-L-L-s!!", "these b***s!!"),
			array("yourballs", "yourb***s"),
			array("youRBalls", "youRb***s"),
			array("ballsof yours", "b***sof yours"),
			array("baLLss", "b***ss"),
			
			// unicode:
			array("kość", "k**ć"),
			array("kość,", "k**ć,"),
			array("kość?", "k**ć?"),
			array("kość*", "k**ć*"),
			array("kość.", "k**ć."),
			array("kość#", "k**ć#"),
			array("kość$", "k**ć$"),
			array("kość??", "k**ć??"),
			array(".kość", ".k**ć"),
			array("\kość", "\k**ć"),
			array("Kość", "k**ć"),
			array("KOŚĆ", "k**ć"),
			array("koŚć", "k**ć"),
			array("k.o.ś.ć", "k**ć"),
			array("k--o-ś/ć", "k**ć"),
			array("ko8ś0ć", "k**ć"),
			array("--*ko8ś0ć--", "--*k**ć--"),
			array("the ko8ś0ć", "the k**ć"),
			array("ko8Ś0ć!!", "k**ć!!"),
			array("yourkość", "yourk**ć"),
			array("youRKość", "youRk**ć"),
			array("kośćof yours", "k**ćof yours"),
			array("kOŚĆs", "k**ćs"),
		);
    }
    
    public function dataFuzzyNegativeUnicode() {
		return array(
			array("balllss", "balllss"),
			array("1alls", "1alls"),
			
			array("kośść", "kośść"),
			array("k o ś ś ć", "k o ś ś ć"),
		);
	}
}