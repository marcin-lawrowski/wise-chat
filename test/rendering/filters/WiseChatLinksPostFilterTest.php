<?php

WiseChatContainer::load('rendering/filters/post/WiseChatLinksPostFilter');

class WiseChatLinksPostFilterTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @dataProvider dataNoLinks
	 */
    public function testPositiveNoLinks($input, $output) {
		$input = str_replace('"', '&quot;', $input);
        $filter = new WiseChatLinksPostFilter();
		$this->assertEquals($output, $filter->filter($input, false));
    }
    
    public function dataNoLinks() {
		return array(
			array('', ''),
			array('[link src="wp.pl"]', 'wp.pl'),
			array('[link src="wp.pl]"]', 'wp.pl]'),
			array('[link src="wp.pl\'"]', 'wp.pl\''),
			array('test1 [link src="wp.pl"] test2', 'test1 wp.pl test2'),
			array('test1 [link src="wp.pl"] [link src="wp.pl"] test2', 'test1 wp.pl wp.pl test2'),
			array('test1 [link src="wp.pl"] [link src="wp.pl"] [link src="http://wp.pl"] test2', 'test1 wp.pl wp.pl http://wp.pl test2'),
			array('test1 [link src="http://wp.pl"] test2', 'test1 http://wp.pl test2'),
			array('test1 [link src="http://wp.pl/?ss"] test2', 'test1 http://wp.pl/?ss test2'),
			array('test1 [link src="https://www.google.pl/webhp?hl=pl#hl=pl&q=pozna%C5%84+%22:)%22"] test2', 'test1 https://www.google.pl/webhp?hl=pl#hl=pl&q=pozna%C5%84+%22:)%22 test2'),
			array('test1 [link src="http://wp.pl?oop=sss&eee=qqq+333"] test2', 'test1 http://wp.pl?oop=sss&eee=qqq+333 test2'),
			
			// special cases:
			array('[link src="wp.pl"', '[link src=&quot;wp.pl&quot;'),
			array('[link src="wp.pl]', '[link src=&quot;wp.pl]'),
		);
    }
    
    /**
	 * @dataProvider dataWithLinks
	 */
    public function testPositiveWithLinks($input, $output) {
		$input = str_replace('"', '&quot;', $input);
        $filter = new WiseChatLinksPostFilter();
		$this->assertEquals($output, $filter->filter($input, true));
    }
    
    public function dataWithLinks() {
		return array(
			array('', ''),
			array('[link src="wp.pl"]', '<a href="http://wp.pl" target="_blank" rel="nofollow">wp.pl</a>'),
			array('[link src="wp.pl]"]', '<a href="http://wp.pl]" target="_blank" rel="nofollow">wp.pl]</a>'),
			array('test1 [link src="wp.pl"] test2', 'test1 <a href="http://wp.pl" target="_blank" rel="nofollow">wp.pl</a> test2'),
			array('test1 [link src="wp.pl"] [link src="wp.pl"] test2', 'test1 <a href="http://wp.pl" target="_blank" rel="nofollow">wp.pl</a> <a href="http://wp.pl" target="_blank" rel="nofollow">wp.pl</a> test2'),
			array('test1 [link src="wp.pl"] [link src="wp.pl"] [link src="http://wp.pl"] test2', 'test1 <a href="http://wp.pl" target="_blank" rel="nofollow">wp.pl</a> <a href="http://wp.pl" target="_blank" rel="nofollow">wp.pl</a> <a href="http://wp.pl" target="_blank" rel="nofollow">http://wp.pl</a> test2'),
			array('test1 [link src="http://wp.pl/?ss"] test2', 'test1 <a href="http://wp.pl/?ss" target="_blank" rel="nofollow">http://wp.pl/?ss</a> test2'),
			array('test1 [link src="https://www.google.pl/webhp?hl=pl#hl=pl&q=pozna%C5%84+%22:)%22"] test2', 'test1 <a href="https://www.google.pl/webhp?hl=pl#hl=pl&q=pozna%C5%84+%22:)%22" target="_blank" rel="nofollow">https://www.google.pl/webhp?hl=pl#hl=pl&amp;q=poznań &quot;:)&quot;</a> test2'),
			array('test1 [link src="http://wp.pl?oop=sss&eee=qqq+333"] test2', 'test1 <a href="http://wp.pl?oop=sss&eee=qqq+333" target="_blank" rel="nofollow">http://wp.pl?oop=sss&amp;eee=qqq 333</a> test2'),

			array('[link src="wp.pl]', '[link src=&quot;wp.pl]'),
		);
    }
    
    
}