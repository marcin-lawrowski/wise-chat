<?php

WiseChatContainer::load('rendering/WiseChatTemplater');

class WiseChatTemplaterTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @dataProvider data
	 */
    public function testTemplater($template, $data, $output) {
		$templater = new WiseChatTemplater('');
		$templater->setTemplate($template);
		$actualOutput = $templater->render($data);
		
		$this->assertEquals($output, $actualOutput);
    }
    
    public function data() {
		return array(
			array("", array(), ""),
			array("{{ x }}", array('x' => 't1'), "t1"),
			array("{{ x }} a {{ y }}", array('x' => 't1', 'y' => 't2'), "t1 a t2"),
			array("{{x }} a {{    y 	}}", array('x' => 't1', 'y' => 't2'), "t1 a t2"),
			array("{{ x }} a {{ y }} b {{ x }}", array('x' => 't1', 'y' => 't2'), "t1 a t2 b t1"),
			
			array("{% if x %} c1 {% endif x %}", array('x' => true), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => '1'), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => ''), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => '1'), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => ''), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => 12), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => 0), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => 12), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => 0), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => 12.43), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => 0.0), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => 12.53), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => 0.0), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => array(1, 2)), "c1"),
            array("{% if x %} c1 {% endif x %}", array('x' => array()), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => array(1, 2)), ""),
            array("{% if !x %} c1 {% endif x %}", array('x' => array()), "c1"),
            array("{% if !x %} c1 {% endif x %}", array('x' => null), "c1"),
			array("{%if    x%}c1{%endif x%}", array('x' => true), "c1"),
			array("{% if x %} c1 {% endif x %}", array('x' => false), ""),
			array("{% if x %} c1 {% endif x %} a {% if x %} c2 {% endif x %}", array('x' => true), "c1 a c2"),
			array("{% if x %} c1 {% endif x %} a {%if y%} c2 {%endif y%}", array('x' => true, 'y' => true), "c1 a c2"),
			array("{% if x %} c1 {% endif x %} a {%if y%} c2 {%endif y%} b {% if x %} c3 {% endif x %}", array('x' => true, 'y' => true), "c1 a c2 b c3"),
			
			array("{% if x %} c1 {%if y%} a {%endif y%} c2 {% endif x %} {%if y%} c3 {%endif y%}", array('x' => true, 'y' => true), "c1 a c2 c3"),
			array("{% if x %} c1\nc2\n\n{% endif x %}", array('x' => true), "c1\nc2"),
			
			array("1 {% if x %} a {{y}} {%if x %} b {% endif x %} c {% if z %} d {% endif z %} e {% endif x %} f", array('x' => true, 'y' => 'v', 'z' => false), "1 a v b c  e f"),
			
			array("{% if !x %} c1 {% endif x %}", array('x' => false), "c1"),
			array("{% if !x %} c1 {% endif x %} c2{%if x %} c3 {% endif x %}", array('x' => false), "c1 c2"),
			array("{% if !x %} c1{%if x %} c3 {% endif x %} {% endif x %} c2{%if x %} c3 {% endif x %}", array('x' => false), "c1 c2"),
			
			array("{% variable v %} c {% endvariable v %} {{v}}", array(), " c"),
			array("{%variable v%} c {%    endvariable v%} {{v}}", array(), " c"),
			
			array("{% variable v %} c1 {% if x %} c2 {% endif x %} {% endvariable v %} {{v}}", array('x' => true), " c1 c2"),
		);
    }
}