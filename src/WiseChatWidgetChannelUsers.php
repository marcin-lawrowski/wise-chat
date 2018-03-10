<?php

/**
 * Wise Chat widget for displaying users of a channel.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatWidgetChannelUsers extends WP_Widget {

	public function __construct() {
		$widgetOps = array('classname' => 'WiseChatWidgetChannelUsers', 'description' => 'Displays Wise Chat channel users' );
		parent::__construct('WiseChatWidgetChannelUsers', 'Wise Chat Channel Users', $widgetOps);
	}

	public function form($instance) {
		$instance = wp_parse_args((array) $instance, array('channel' => '', 'options' => '', 'title' => 'Chat users'));

		$title = $instance['title'];
		$channel = $instance['channel'];
		$options = $instance['options'];
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
								name="<?php echo $this->get_field_name('title'); ?>"
								type="text" value="<?php echo esc_attr($title); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('channel'); ?>">
				Channel: <input class="widefat" id="<?php echo $this->get_field_id('channel'); ?>"
								name="<?php echo $this->get_field_name('channel'); ?>"
								type="text" value="<?php echo esc_attr($channel); ?>" />
			</label>
		</p>
		<p class="description">Empty field means the default <i>global</i> channel.</p>
		<p>
			<label for="<?php echo $this->get_field_id('options'); ?>">
				Options: <input class="widefat" id="<?php echo $this->get_field_id('options'); ?>"
										  name="<?php echo $this->get_field_name('options'); ?>"
										  type="text" value="<?php echo esc_attr($options); ?>" />
			</label>

		</p>
		<p class="description">You can use here the same attributes as in [wise-chat] shortcode.</p>
		<?php
	}

	public function update($newInstance, $oldInstance) {
		$instance = $oldInstance;
		$instance['title'] = $newInstance['title'];
		$instance['channel'] = $newInstance['channel'];
		$instance['options'] = $newInstance['options'];

		return $instance;
	}

	public function widget($args, $instance) {
		extract($args, EXTR_SKIP);

		echo $before_widget;

		$wiseChat = WiseChatContainer::get('WiseChat');
		$title = $instance['title'];
		$channel = $instance['channel'];
		$options = $instance['options'];

		$parsedOptions = shortcode_parse_atts($options);

		if (!is_array($parsedOptions)) {
			$parsedOptions = array();
		}
		$parsedOptions['channel'] = $channel;
		$parsedOptions['title'] = $title;
		echo $wiseChat->getRenderedChannelUsersShortcode($parsedOptions);

		echo $after_widget;

		$wiseChat->registerResources();
	}
}