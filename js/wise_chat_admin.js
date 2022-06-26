/**
 * Wise Chat admin support JS
 *
 * @author Kainex <contact@kaine.pl>
 * @link https://kaine.pl/projects/wp-plugins/wise-chat-pro
 */
jQuery(document).ready(function($){
	jQuery('.wc-color-picker').wpColorPicker();
	jQuery('.wc-image-picker').click(function(e) {
		e.preventDefault();
		var button = jQuery(this);
		var targetId = button.data('target-id');
		var imageContainerId = button.data('image-container-id');
		var target = jQuery('#' + targetId);
		var imageContainer = jQuery('#' + imageContainerId);
		var frame = wp.media({
			title: 'Select or Upload Emoticon Image',
			button: {
				text: 'Use this image'
			},
			multiple: false
		});

		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();

			target.val(attachment.id);
			imageContainer.html('<img src="' + attachment.url + '" style="max-width: 100px;" />');
		});

		frame.open();
	});

	jQuery("form input[type='checkbox']").change(function(event) {
		var target = jQuery(event.target);
		var childrenSelector = "*[data-parent-field='" + target.attr('id') + "']";
		if (target.is(':checked')) {
			jQuery(childrenSelector).attr('disabled', null);
		} else {
			jQuery(childrenSelector).attr('disabled', '1');
		}
	});

	var childrenSelector = "*[data-parent-field='custom_emoticons_enabled']";
	if (jQuery("input#custom_emoticons_enabled").is(':checked')) {
		jQuery(childrenSelector).attr('disabled', null);
	} else {
		jQuery(childrenSelector).attr('disabled', '1');
	}

	function addCheckboxesBind(parentCheckbox, childrenCheckboxesName, selectCheckboxesName) {
		jQuery(parentCheckbox).change(function(event) {
			if (!this.checked) {
				return;
			}

			var areAccessRolesSelected = false;
			jQuery(childrenCheckboxesName).each(function () {
				if (this.checked) {
					areAccessRolesSelected = true;
				}
			});

			if (areAccessRolesSelected === false) {
				jQuery(selectCheckboxesName).each(function () {
					jQuery(this).prop('checked', true);
				});
			}
		});
	}

	addCheckboxesBind(
		'#access_mode', "input[name='wise_chat_options_name[access_roles][]'", "input[name='wise_chat_options_name[access_roles][]'"
	);


	jQuery( ".wc-radio-option" ).change(function() {
		jQuery('.wc-radio-hint-group-' + $(this).data('radio-group-id')).hide();
		jQuery('.wc-radio-hint-' + $(this).attr('id')).show();
	});

});