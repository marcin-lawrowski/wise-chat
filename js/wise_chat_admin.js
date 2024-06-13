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

	jQuery('.new-emoticon-submit').click(function(e) {
		var attachmentId = jQuery('#newEmoticonId').val();
		var alias = jQuery('#newEmoticonAlias').val();
		if (attachmentId.length === 0) {
			e.preventDefault();
			alert('Please select the image first.');
			return;
		}
		var href = jQuery(this).attr('href') + '&newEmoticonAttachmentId=' + encodeURIComponent(attachmentId) + '&newEmoticonAlias=' + encodeURIComponent(alias) + '&tab=emoticons';
		jQuery(this).attr('href', href);
	});

	jQuery('.new-pm-rule-submit').click(function(e) {
		var source = jQuery('[name="newPmRuleSource"]').val();
		var target = jQuery('[name="newPmRuleTarget"]').val();

		var href = jQuery(this).attr('href') + '&newPmRuleSource=' + encodeURIComponent(source) + '&newPmRuleTarget=' + encodeURIComponent(target) + '&tab=permissions';
		jQuery(this).attr('href', href);
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

	jQuery('.wc-save-notification-button').click(function(e) {
		var form = jQuery(this).closest('.wc-notification-form');
		var action = form.find('#notificationAction').val();
		var frequency = form.find('#notificationFrequency').val();
		var recipientEmail = form.find('#notificationRecipientEmail').val();
		var subject = form.find('#notificationSubject').val();
		var content = form.find('#notificationContent').val();

		if (recipientEmail.length === 0) {
			alert('Recipient\'s e-mail is required.');
			e.preventDefault();
		} else if (subject.length === 0) {
			alert('Subject is required.');
			e.preventDefault();
		} else if (content.length === 0) {
			alert('Content is required.');
			e.preventDefault();
		} else {
			var href = jQuery(this).attr('href') +
				'&action=' + encodeURIComponent(action) +
				'&frequency=' + encodeURIComponent(frequency) +
				'&recipientEmail=' + encodeURIComponent(recipientEmail) +
				'&subject=' + encodeURIComponent(subject) +
				'&content=' + encodeURIComponent(content) +
				'&tab=notifications';
			jQuery(this).attr('href', href);
		}
	});

	jQuery('.wc-save-user-notification-button').click(function(e) {
		var form = jQuery(this).closest('.wc-user-notification-form');
		var frequency = form.find('#userNotificationFrequency').val();
		var subject = form.find('#userNotificationSubject').val();
		var content = form.find('#userNotificationContent').val();

		if (subject.length === 0) {
			alert('Subject is required.');
			e.preventDefault();
		} else if (content.length === 0) {
			alert('Content is required.');
			e.preventDefault();
		} else {
			var href = jQuery(this).attr('href') +
				'&frequency=' + encodeURIComponent(frequency) +
				'&subject=' + encodeURIComponent(subject) +
				'&content=' + encodeURIComponent(content) +
				'&tab=notifications';
			jQuery(this).attr('href', href);
		}
	});

	/* User search feature */
	var userSearchTimeout;
	jQuery('input.wcUserLoginHint').keyup(function(e) {
		clearTimeout(userSearchTimeout);
		userSearchTimeout = setTimeout(function(target) { return userSearch(target); }(jQuery(this)), 500);
	});

	function userSearch(target) {
		var keyword = target.val();
		if (keyword.length === 0) {
			return;
		}

		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: wcAdminConfig.ajaxurl,
			data: { action: "wise_chat_admin_user_search", keyword: keyword },
			success: function(response) {
				if (response.type === "success") {
					var layer = jQuery('<div class="wcUserSearchLayer">');
					layer.append(response.users.map(function(user) {
						return jQuery('<a>').data('login', user.login).attr('href', '#').text(user.text).click(function(e) {
							e.preventDefault();
							target.val(jQuery(this).data('login'));
						});
					}));
					jQuery('.wcUserSearchLayer').remove();
					target.after(layer);
				}
				else {
					alert("Error searching users");
				}
			}
		});
	}

	jQuery('body').click(function(e) {
		jQuery('.wcUserSearchLayer').remove();
	});

	jQuery('.wc-add-moderator-button').click(function(e) {
		var user = jQuery('.wc-add-moderator-user-login').val();
		var rights = jQuery('.wc-add-moderator-right:checked');
		if (rights.length === 0) {
			e.preventDefault();
			alert('Please select the moderation rights.');
			return;
		}
		if (!user) {
			e.preventDefault();
			alert('Please select the user login.');
			return;
		}

		var mappedRights = [];
		rights.each(function(index, element) {
			mappedRights.push($(element).val());
		});

		jQuery(this).attr('href', jQuery(this).attr('href') + '&tab=moderation&addModeratorUserLogin=' + encodeURIComponent(user) + '&addModeratorRights=' + encodeURIComponent(mappedRights.join(',')));
	});

	jQuery( ".wc-radio-option" ).change(function() {
		refreshRadioGroup(this);
	});

	jQuery(".wc-radio-option:checked").each(function(index, element) {
		refreshRadioGroup(element);
	});

	function refreshRadioGroup(element) {
		jQuery('.wc-radio-hint-group-' + $(element).data('radio-group-id')).hide();
		jQuery('.wc-radio-hint-' + $(element).attr('id')).show();

		jQuery( ".wc-radio-option[data-radio-group-id=" + $(element).data('radio-group-id') + "]" ).each(function (index, element) {
			var groupDef = $(this).data('group-def');
			if (groupDef) {
				var groupElements = groupDef.split(',');
				for (var x = 0; x < groupElements.length; x++) {
					jQuery( '[name=wise_chat_options_name\\[' + groupElements[x] + '\\]]' ).closest('tr').hide();
					jQuery( '[data-section-id=' + groupElements[x] + ']').hide();
				}
			}
		});

		var groupDef = $(element).data('group-def');
		if (groupDef) {
			var groupElements = groupDef.split(',');
			for (var x = 0; x < groupElements.length; x++) {
				jQuery( '[name=wise_chat_options_name\\[' + groupElements[x] + '\\]]' ).closest('tr').show();
				jQuery( '[data-section-id=' + groupElements[x] + ']').show();
			}
		}
	}

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