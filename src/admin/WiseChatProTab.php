<?php 

/**
 * Wise Chat admin pro settings tab class.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
class WiseChatProTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array(
				'_section', 'Wise Chat Pro Features',
				'<a href="http://kaine.pl/projects/wp-plugins/wise-chat-pro?source=settings-page"><img src="'.$this->options->getBaseDir().'/gfx/pro/wordpress-wise-chat-pro.png" /></a>'.
				'<style type="text/css">#wise-chat-proContainer .button { display: none; } #wise-chat-proContainer ul li { font-size: 1.3em; }</style>'.
				'<br />'.
				'<h2>Boost your users engagement by 300% - try Wise Chat Pro!</h2>'.
				'<ul>'.
				'<li>&#8226; Wise Chat Pro = Wise Chat + Pro features + 6-month upgrade</li>'.
				'<li>&#8226; Private one-to-one chats</li>'.
				'<li>&#8226; Avatars</li>'.
				'<li>&#8226; External authentication (Facebook, Twitter or Google+)</li>'.
				'<li>&#8226; WordPress multisite support</li>'.
				'<li>&#8226; Three Pro themes</li>'.
				'</ul>'.
				'<a class="button-secondary wcAdminButtonPro" target="_blank" href="http://kaine.pl/projects/wp-plugins/wise-chat-pro?source=settings-page" title="Check Wise Chat Pro">
					Check Wise Chat <strong>Pro</strong>
				</a>'
			),

		);
	}
	
	public function getDefaultValues() {
		return array(

		);
	}
}