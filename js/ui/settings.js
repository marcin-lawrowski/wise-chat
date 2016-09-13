/**
 * Wise Chat user's settings support.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatSettings(options, messages) {
	var settingsEndpoint = options.apiEndpointBase + '?action=wise_chat_settings_endpoint';
	var container = jQuery('#' + options.chatId);
	var currentUserName = container.find('.wcCurrentUserName');
	var customizeButton = container.find('a.wcCustomizeButton');
	var customizationsPanel = container.find('.wcCustomizationsPanel');
	var userNameInput = container.find('.wcCustomizationsPanel input.wcUserName');
	var userNameApproveButton = container.find('.wcCustomizationsPanel input.wcUserNameApprove');
	var muteSoundCheckbox = container.find('.wcCustomizationsPanel input.wcMuteSound');
	var textColorInput = container.find('.wcCustomizationsPanel input.wcTextColor');
	var textColorResetButton = container.find('.wcCustomizationsPanel input.wcTextColorReset');
	
	/**
	 * Saves given property on the server side using AJAX call.
	 * 
	 * @param {String} propertyName
	 * @param {String} propertyValue
	 * @param {Function} successCallback
	 * @param {Function} errorCallback
	 */
	function saveProperty(propertyName, propertyValue, successCallback, errorCallback) {
		jQuery.ajax({
			type: "POST",
			url: settingsEndpoint,
			data: {
				property: propertyName,
				value: propertyValue,
                channelId: options.channelId,
				checksum: options.checksum
			}
		})
		.done(function(result) {
			onPropertySaveRequestSuccess(result, successCallback);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
            messages.logDebug('[saveProperty] ' + jqXHR.responseText);
			onPropertySaveRequestError(jqXHR, textStatus, errorThrown, errorCallback);
		});
	}
	
	/**
	 * Processes AJAX success response. 
	 * 
	 * @param {String} result
	 * @param {Function callback
	 */
	function onPropertySaveRequestSuccess(result, callback) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				messages.showErrorMessage(response.error);
			} else {
				if (typeof(callback) != 'undefined') {
					callback.apply(this, [response]);
				}
			}
		}
		catch (e) {
			showServerError(e.toString());
		}
	}
	
	/**
	 * Processes AJAX error response. 
	 * 
	 * @param {String} result
	 * @param {Function callback
	 */
	function onPropertySaveRequestError(jqXHR, textStatus, errorThrown, callback) {
		try {
			var response = jQuery.parseJSON(jqXHR.responseText);
			if (response.error) {
				showServerError(response.error);
			} else {
				showServerError('Unknown server error: ' + errorThrown);
			}
		}
		catch (e) {
			showServerError('Fatal error: ' + errorThrown);
		}

		if (typeof(callback) != 'undefined') {
			callback.apply(this, [errorThrown]);
		}
	}
	
	/**
	 * Displays server error. It indicates a serious server-side problem.
	 * 
	 * @param {String} errorMessage
	 */
	function showServerError(errorMessage) {
		messages.showErrorMessage(errorMessage);
	};
	
	function onUserNameApproveButtonClick(e) {
		var userNameInputElement = userNameInput[0];
		if (typeof (userNameInputElement.checkValidity) == 'function') {
			userNameInputElement.checkValidity();
		}
		
		var userName = userNameInput.val().replace(/^\s+|\s+$/g, '');
		if (userName.length > 0) {
			saveProperty('userName', userName, function(response) {
				currentUserName.html(response.value + ':');
				customizationsPanel.fadeOut();
			});
		}
	};
	
	function onMuteSoundCheckboxChange(e) {
		saveProperty('muteSounds', muteSoundCheckbox.is(':checked'), function(response) {
			options.userSettings.muteSounds = muteSoundCheckbox.is(':checked');
			customizationsPanel.fadeOut();
		});
	}
	
	function onTextColorChange(id, value) {
		saveProperty('textColor', value, function(response) {
			options.userSettings.textColor = value != 'null' ? value : '';
			customizationsPanel.fadeOut();
		});
	}

	function onTextColorResetButtonClick(e) {
		onTextColorChange('', 'null');
		textColorInput.parent().find('.colorPicker-picker').css({
			backgroundColor: textColorInput.parent().css('color')
		})
	}

	// DOM events:
	customizeButton.click(function(e) {
		customizationsPanel.toggle();
	});
	userNameApproveButton.click(onUserNameApproveButtonClick);
	muteSoundCheckbox.change(onMuteSoundCheckboxChange);

	if (typeof textColorInput.colorPicker != 'undefined') {
		var colorsPalette = [
			'330000', '331900', '333300', '193300', '003300', '003319', '003333', '001933',
			'000033', '190033', '330033', '330019', '000000', '660000', '663300', '666600', '336600',
			'006600', '006633', '006666', '003366', '000066', '330066', '660066', '660033', '202020',
			'990000', '994c00', '999900', '4c9900', '009900', '00994c', '009999', '004c99', '000099',
			'4c0099', '990099', '99004c', '404040', 'cc0000', 'cc6600', 'cccc00', '66cc00', '00cc00',
			'00cc66', '00cccc', '0066cc', '0000cc', '6600cc', 'cc00cc', 'cc0066', '606060', 'ff0000',
			'ff8000', 'ffff00', '80ff00', '00ff00', '00ff80', '00ffff', '0080ff', '0000ff', '7f00ff',
			'ff00ff', 'ff007f', '808080', 'ff3333', 'ff9933', 'ffff33', '99ff33', '33ff33', '33ff99',
			'33ffff', '3399ff', '3333ff', '9933ff', 'ff33ff', 'ff3399', 'a0a0a0', 'ff6666', 'ffb266',
			'ffff66', 'b2ff66', '66ff66', '66ffb2', '66ffff', '66b2ff', '6666ff', 'b266ff', 'ff66ff',
			'ff66b2', 'c0c0c0', 'ff9999', 'ffcc99', 'ffff99', 'ccff99', '99ff99', '99ffcc', '99ffff',
			'99ccff', '9999ff', 'cc99ff', 'ff99ff', 'ff99cc', 'e0e0e0', 'ffcccc', 'ffe5cc', 'ffffcc',
			'e5ffcc', 'ccffcc', 'ccffe5', 'ccffff', 'cce5ff', 'ccccff', 'e5ccff', 'ffccff', 'ffcce5',
			'ffffff'
		];
		var defaultColor = options.userSettings.textColor;
		if (typeof defaultColor == "undefined" || defaultColor.length == 0) {
			defaultColor = textColorInput.parent().css('color');
		}

		textColorInput.colorPicker({
			pickerDefault: defaultColor,
			colors: colorsPalette,
			showHexField: false,
			onColorChange: onTextColorChange
		});
		textColorResetButton.click(onTextColorResetButtonClick);
	}
};
