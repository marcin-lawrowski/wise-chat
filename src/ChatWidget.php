<?php

namespace Kainex\WiseChat;

use WP_Widget;

/**
 * Wise Chat widget.
 *
 * @author Kainex <contact@kaine.pl>
 */
class ChatWidget extends WP_Widget {

	/** @var WiseChat */
	private $wiseChat;

	/**
	 * @param WiseChat $wiseChat
	 */
	public function __construct(WiseChat $wiseChat) {
		$this->wiseChat = $wiseChat;
		$widgetOps = array('classname' => 'WiseChatWidget', 'description' => 'Displays Wise Chat' );
		parent::__construct('WiseChatWidget', 'Wise Chat Window', $widgetOps);
	}
 
	public function form($instance) {
		$instance = wp_parse_args((array) $instance, array('channel' => '', 'options' => ''));
		
		$channel = $instance['channel'];
		$options = $instance['options'];
		?>
			<p>
				<label for="<?php echo $this->get_field_id('channel'); ?>">
					Channel: <input class="widefat" id="<?php echo $this->get_field_id('channel'); ?>" 
								name="<?php echo $this->get_field_name('channel'); ?>" 
								type="text" value="<?php echo esc_attr($channel); ?>" />
				</label>
			</p>
            <p>
                <label for="<?php echo $this->get_field_id('options'); ?>">
                    Shortcode options: <input class="widefat" id="<?php echo $this->get_field_id('options'); ?>"
                                    name="<?php echo $this->get_field_name('options'); ?>"
                                    type="text" value="<?php echo esc_attr($options); ?>" />
                </label>
            </p>
		<?php
	}
 
	public function update($newInstance, $oldInstance) {
		$instance = $oldInstance;
		$instance['channel'] = $newInstance['channel'];
		$instance['options'] = $newInstance['options'];
		
		return $instance;
	}
	
	public function widget($args, $instance) {
		extract($args, EXTR_SKIP);

		$channel = isset($instance['channel']) ? $instance['channel'] : 'global';
		$options = isset($instance['options']) ? $instance['options'] : '';

		$parsedOptions = shortcode_parse_atts($options);
		if (!is_array($parsedOptions)) {
			$parsedOptions = array('channel' => $channel);
		} else {
			$parsedOptions['channel'] = $channel;
		}

		echo $this->wiseChat->getRenderedShortcode($parsedOptions);
	}
}