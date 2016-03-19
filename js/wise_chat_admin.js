/**
 * Wise Chat admin support JS
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
jQuery(document).ready(function($){
	jQuery('.wc-color-picker').wpColorPicker();
	
	jQuery("form input[type='checkbox']").change(function(event) {
		var target = jQuery(event.target);
		var childrenSelector = "*[data-parent-field='" + target.attr('id') + "']";
		if (target.is(':checked')) {
			jQuery(childrenSelector).attr('disabled', null);
		} else {
			jQuery(childrenSelector).attr('disabled', '1');
		}
	});
});