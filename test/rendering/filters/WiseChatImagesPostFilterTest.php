<?php

WiseChatContainer::load('rendering/filters/post/WiseChatImagesPostFilter');

class WiseChatImagesPostFilterTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider data
	 */
    public function testPositiveImages($input, $output) {
		$input = str_replace('"', '&quot;', $input);
        $postFilter = new WiseChatImagesPostFilter();
		$this->assertEquals($output, $postFilter->filter($input, true));
    }
    
    public function data() {
		return array(
			array(
				'[img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="ORG"]', 
				'<a href="http://www.poznan.pl/sdsd.jpg" target="_blank" data-lightbox="wise_chat" rel="lightbox[wise_chat]"><img src="http://www.poznan.pl/sdsd-th.jpg" class="wcImage" /></a>'
			),
			array(
				'H: [img id="1" src="http://www.poznan.pl/sdsd.jpg?aaa=%20ss" src-th="http://www.poznan.pl/sdsd-th.jpg?bbb=%20ss" src-org="ORG"]', 
				'H: <a href="http://www.poznan.pl/sdsd.jpg?aaa=%20ss" target="_blank" data-lightbox="wise_chat" rel="lightbox[wise_chat]"><img src="http://www.poznan.pl/sdsd-th.jpg?bbb=%20ss" class="wcImage" /></a>'
			),
			array(
				'H: [img id="1" src="http://www.poznan.pl/sdsd.jpg?aaa=%20ss" src-th="http://www.poznan.pl/sdsd-th.jpg?bbb=%20ss" src-org="ORG"] x [img id="1" src="http://www.poznan.pl/www.jpg?aaa=%20ss" src-th="http://www.poznan.pl/www-th.jpg?bbb=%20ss" src-org="ORG"]', 
				'H: <a href="http://www.poznan.pl/sdsd.jpg?aaa=%20ss" target="_blank" data-lightbox="wise_chat" rel="lightbox[wise_chat]"><img src="http://www.poznan.pl/sdsd-th.jpg?bbb=%20ss" class="wcImage" /></a> x <a href="http://www.poznan.pl/www.jpg?aaa=%20ss" target="_blank" data-lightbox="wise_chat" rel="lightbox[wise_chat]"><img src="http://www.poznan.pl/www-th.jpg?bbb=%20ss" class="wcImage" /></a>'
			),
		);
    }
    
    /**
	 * @dataProvider dataNoImagesButLinks
	 */
    public function testPositiveNoImagesButLinks($input, $output) {
		$input = str_replace('"', '&quot;', $input);
        $postFilter = new WiseChatImagesPostFilter();
		$this->assertEquals($output, $postFilter->filter($input, false, true));
    }
    
    public function dataNoImagesButLinks() {
		return array(
			array(
				'[img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="www.wroc.pl/org.jpg"]', 
				'<a href="http://www.wroc.pl/org.jpg" target="_blank" rel="nofollow">www.wroc.pl/org.jpg</a>'
			),
			array(
				'[img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="http://www.wroc.pl/org.jpg"]', 
				'<a href="http://www.wroc.pl/org.jpg" target="_blank" rel="nofollow">http://www.wroc.pl/org.jpg</a>'
			),
			array(
				'H: [img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="www.wroc.pl/org.jpg?q=pozna%C5%84+%22:)%22"]', 
				'H: <a href="http://www.wroc.pl/org.jpg?q=pozna%C5%84+%22:)%22" target="_blank" rel="nofollow">www.wroc.pl/org.jpg?q=poznań &quot;:)&quot;</a>'
			),
		);
    }
    
    /**
	 * @dataProvider dataNoImagesNoLinks
	 */
    public function testPositiveNoImagesNoLinks($input, $output) {
		$input = str_replace('"', '&quot;', $input);
        $postFilter = new WiseChatImagesPostFilter();
		$this->assertEquals($output, $postFilter->filter($input, false, false));
    }
    
    public function dataNoImagesNoLinks() {
		return array(
			array(
				'[img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="www.wroc.pl/org.jpg"]', 
				'www.wroc.pl/org.jpg'
			),
			array(
				'[img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="http://www.wroc.pl/org.jpg"]', 
				'http://www.wroc.pl/org.jpg'
			),
			array(
				'H: [img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="www.wroc.pl/org.jpg?q=pozna%C5%84+%22:)%22"]', 
				'H: www.wroc.pl/org.jpg?q=pozna%C5%84+%22:)%22'
			),
			
			array(
				'H: [img id="1" src="http://www.poznan.pl/sdsd.jpg" src-th="http://www.poznan.pl/sdsd-th.jpg" src-org="www.wroc.pl/org.jpg?q=pozna%C5%84+%22:)%22"] http://www.ww.pl s', 
				'H: www.wroc.pl/org.jpg?q=pozna%C5%84+%22:)%22 http://www.ww.pl s'
			),
		);
    }
}