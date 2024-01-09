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

	jQuery('.wc-advanced-diagnostics-run').on('click', function () {
		var resultDefault = 'OK';
		var resultLightweight = 'Checking ...';
		var resultUltra = 'Checking ...';
		var resultGold = 'Checking ...';
		function renderSingleResult(result) {
			if (result === 'OK') {
				return '<span style="color: green;">OK</span>'
			}
			if (result === 'Checking ...') {
				return 'Checking ...'
			}
			return '<span style="color: red;">Error: ' + result + '</span>';
		}
		function renderResults() {
			var result = '';
			result += "<strong>Default:</strong><br />" + renderSingleResult(resultDefault) + '<br /><br />';
			result += "<strong>Lightweight:</strong><br />" + renderSingleResult(resultLightweight) + '<br /><br />';
			result += "<strong>Ultra Lightweight:</strong><br />" + renderSingleResult(resultUltra) + '<br /><br />';
			result += "<strong>Gold:</strong><br />" + renderSingleResult(resultGold);
			jQuery('.wc-advanced-diagnostics-result').html(result);
		}
		// Lightweight
		jQuery.ajax({ type: "get", dataType: "text", url: wcAdminConfig.pluginurl + 'endpoints/', data: { action: "check" } })
		.done(function(response) {
			if (response === 'OK') {
				resultLightweight = 'OK';
			} else {
				resultLightweight = 'Please try disabling debug mode in both WordPress and Wise Chat';
			}
			renderResults();
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			resultLightweight = 'Code ' + jqXHR.status + '. ';
			if (jqXHR.status === 400) {
				resultLightweight += 'Please de-activate the plugin and activate it again.';
			} else if (jqXHR.status < 500) {
				resultLightweight += 'The chat is being blocked by a security plugin or the server. Please make sure that PHP files may be directly executed here: ' +
					wcAdminConfig.pluginurl + 'endpoints/';
			} else {
				resultLightweight += 'Please make sure you run your site with newest the Wise Chat, WordPress and PHP.';
			}
			renderResults();
		});
		// Ultra
		jQuery.ajax({ type: "get", dataType: "text", url: wcAdminConfig.pluginurl + 'endpoints/ultra/index.php', data: { action: "check", channelIds: [1], lastId: 0, lastCheckTime: 0, fromActionId: 0 } })
		.done(function(response) {
			if (response === 'OK') {
				resultUltra = 'OK';
			} else {
				resultUltra = 'Please try disabling debug mode in both WordPress and Wise Chat';
			}
			renderResults();
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			resultUltra = 'Code ' + jqXHR.status + '. ';
			var response = jQuery.parseJSON(jqXHR.responseText);
			var errorMessage = response && response.error ? response.error : 'no further details found';
			if (jqXHR.status === 400) {
				resultUltra += 'Please confirm that you use standard wp-config.php and wp-load.php files in their standard locations. Details: ' + errorMessage;
			} else if (jqXHR.status < 500) {
				resultUltra += 'The chat is being blocked by a security plugin or the server. Please make sure that PHP files may be directly executed here: ' +
					wcAdminConfig.pluginurl + 'endpoints/ultra/index.php';
			} else {
				resultUltra += 'Please make sure you run your site with the newest Wise Chat, WordPress and PHP.';
			}
			renderResults();
		});
		// gold
		jQuery.ajax({ type: "get", dataType: "text", url: wcAdminConfig.siteurl, data: { action: "check", 'wc-gold-engine': 1 } })
		.done(function(response) {
			if (response === 'OK') {
				resultGold = 'OK';
			} else {
				resultGold = 'It looks Gold engine is not installed yet. The engine will be installed automatically once you switch to it.';
			}
			renderResults();
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			resultGold = 'Code ' + jqXHR.status + '. ';
			var response = jQuery.parseJSON(jqXHR.responseText);
			var errorMessage = response && response.error ? response.error : 'no further details found';
			if (jqXHR.status === 400) {
				resultGold += 'Engine error: ' + errorMessage;
			} else if (jqXHR.status === 404) {
				resultGold += 'It looks Gold engine is not installed yet. The engine will be installed automatically once you switch to it.';
			} else {
				resultGold += 'Please make sure you run your site with the newest Wise Chat, WordPress and PHP.';
			}
			renderResults();
		});
		renderResults();
	});

});