/**
 * Wise Chat messages sending and displaying. 
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatMessages(options, messagesHistory, messageAttachments, dateAndTimeRenderer, notifier, progressBar) {
	var MESSAGES_REFRESH_TIMEOUT = options.messagesRefreshTime;
	var MESSAGES_ORDER = options.messagesOrder;
	
	var lastId = options.lastId;
	var idsCache = {};
	var channelId = options.channelId;
	var refresherInitialized = false;

	var messagesEndpoint = options.apiEndpointBase + '?action=wise_chat_messages_endpoint';
	var messageEndpoint = options.apiEndpointBase + '?action=wise_chat_message_endpoint';
	var messageDeleteEndpoint = options.apiEndpointBase + '?action=wise_chat_delete_message_endpoint';
	var userBanEndpoint = options.apiEndpointBase + '?action=wise_chat_user_ban_endpoint';

	var container = jQuery('#' + options.chatId);
	var messagesContainer = container.find('.wcMessages');
	var usersListContainer = container.find('.wcUsersList');
	var usersCounter = container.find('.wcUsersCounter span');
	var messagesInput = container.find('.wcInput');
	var currentUserName = container.find('.wcCurrentUserName');
	var isMessageMultiline = options.multilineSupport;
	var submitButton = container.find('.wcSubmitButton');
	var currentRequest = null;
	var lastErrorMessageText = null;
    var debugLog = [];

	function isAscendingOrder() {
		return MESSAGES_ORDER == 'ascending';
	}

	/**
	* Moves the scrollbar to the top (descending order mode) or to the bottom (ascending order mode).
	*/
	function scrollMessages() {
		var scrollPosition = isAscendingOrder() ? messagesContainer[0].scrollHeight : 0;

		setTimeout(function() { setMessagesScollPosition(scrollPosition); }, 200);
	};

	/**
	* Checks whether the scrollbar is set to the top (descending order mode) or to the bottom (ascending order mode).
	*
	* @return {Boolean}
	*/
	function isFullyScrolled() {
		if (isAscendingOrder()) {
            var padding = messagesContainer.innerHeight() - messagesContainer.height();
            return (messagesContainer.height() + messagesContainer.scrollTop() + padding) >= messagesContainer[0].scrollHeight;
		} else {
			return messagesContainer.scrollTop() == 0;
		}
	};

	function setMessagesScollPosition(scrollPosition) {
        messagesContainer.stop().animate({ scrollTop: scrollPosition }, '500', 'swing');
	}

	/**
	 * Corrects position of the scrollbar when new messages are appended or prepended.
	 * It prevents from slight movement of the scrollbar.
	 *
	 * @param {Integer} previousMessagesScrollPosition Previous position of the scrollbar
	 * @param {Integer} previousMessagesScrollHeight Previous height of the scroll area
	 */
	function correctMessagesScrollPosition(previousMessagesScrollPosition, previousMessagesScrollHeight) {
		var messagesNewScrollHeight = messagesContainer[0].scrollHeight;
		var scrollDifference = isAscendingOrder() ? 0 : messagesNewScrollHeight - previousMessagesScrollHeight;
		setMessagesScollPosition(previousMessagesScrollPosition + scrollDifference);
	}

	function showMessage(message) {
		var parsedMessage = jQuery(message);
		if (isAscendingOrder()) {
			messagesContainer.append(parsedMessage);
		} else {
			messagesContainer.prepend(parsedMessage);
		}

		// user mentioning notification:
		var userMentioned = false;
		if (options.mentioningSoundNotification.length > 0 && message.length > 0 && typeof options.userData !== 'undefined') {
			var regexp = new RegExp("@" + options.userData.name, "g");
			if (message.match(regexp)) {
				notifier.sendNotificationForEvent('userMentioning');
				userMentioned = true;
			}
		}

		// send regular notifications instead:
		if (!userMentioned) {
			notifier.sendNotifications();
		}
	};

	function hideMessage(messageId) {
		container.find('div[data-id="' + messageId + '"]').remove();
	}

	function hideAllMessages() {
		container.find('div.wcMessage').remove();
	}

	function replaceUserNameInMessages(renderedUserName, messagesIds) {
		for (var t = 0; t < messagesIds.length; t++) {
			container.find('div[data-id="' + messagesIds[t] + '"] .wcMessageUser').html(renderedUserName);
		}
	}

	function refreshPlainUserName(name) {
		currentUserName.html(name + ':');
	}

    function logDebug(debugText) {
        if (debugLog.length > 20) {
            debugLog.shift();
        }
        debugLog.push('[' + (new Date()) + '] ' + debugText);
    }

    /**
     * Appends the given text to the end of the text in message input field.
     *
     * @param {string} text
     */
    function appendTextToInputField(text) {
        messagesInput.val(messagesInput.val() + text);
        messagesInput.focus();
    }

	function showErrorMessage(message) {
		if (lastErrorMessageText != message) {
			lastErrorMessageText = message;
			message = '<div class="wcMessage wcErrorMessage">' + message + '</div>';
			if (isAscendingOrder()) {
				messagesContainer.append(message);
			} else {
				messagesContainer.prepend(message);
			}
			scrollMessages();
		}
	};

	function showPlainMessage(message) {
		message = '<div class="wcMessage wcPlainMessage">' + message + '</div>';
		if (isAscendingOrder()) {
			messagesContainer.append(message);
		} else {
			messagesContainer.prepend(message);
		}
		scrollMessages();
	};

	function setBusyState(showProgress) {
		submitButton.attr('disabled', '1');
		submitButton.attr('readonly', '1');
		messagesInput.attr('placeholder', options.messages.message_sending);
		messagesInput.attr('readonly', '1');
		if (showProgress == true) {
			progressBar.show();
			progressBar.setValue(0);
		}
	};

	function setIdleState() {
		submitButton.attr('disabled', null);
		submitButton.attr('readonly', null);
		messagesInput.attr('placeholder', options.messages.hint_message);
		messagesInput.attr('readonly', null);
		progressBar.hide();
	};

	function initializeRefresher() {
		if (refresherInitialized == true) {
			return;
		}
		refresherInitialized = true;
		setInterval(checkNewMessages, MESSAGES_REFRESH_TIMEOUT);
	};

	function checkNewMessages() {
		if (currentRequest !== null && currentRequest.readyState > 0 && currentRequest.readyState < 4) {
			return;
		}

		currentRequest = jQuery.ajax({
			type: "GET",
			url: messagesEndpoint,
			data: {
                channelId: channelId,
				lastId: lastId,
				checksum: options.checksum
			}
		})
		.done(onNewMessagesArrived)
		.fail(onMessageArrivalError);

		if (options.debugMode) {
			updateDebugLog();
		}
	};

	function onNewMessagesArrived(result) {
		try {
			var response = jQuery.parseJSON(result);
			if (response.result && response.result.length > 0) {
				var wasFullyScrolled = isFullyScrolled();
				var messagesScrollPosition = messagesContainer.scrollTop();
				var messagesScrollHeight = messagesContainer[0].scrollHeight;

				if (!isAscendingOrder()) {
					response.result.reverse();
				}

				for (var x = 0; x < response.result.length; x++) {
					var msg = response.result[x];
					var messageId = parseInt(msg['id']);
					if (messageId > lastId) {
						lastId = messageId;
					}
					if (!idsCache[messageId]) {
						showMessage(msg['text']);
						idsCache[messageId] = true;
					}
				}

				if (wasFullyScrolled) {
					scrollMessages();
				} else {
					correctMessagesScrollPosition(messagesScrollPosition, messagesScrollHeight);
				}
			}

			dateAndTimeRenderer.convertUTCMessagesTime(container, response.nowTime);

			initializeRefresher();
		}
		catch (e) {
            logDebug('[onNewMessagesArrived] ' + result);
			showErrorMessage('Server error: ' + e.toString());
		}
	};

	function onMessageArrivalError(jqXHR, textStatus, errorThrown) {
        // network problems ignore:
        if (typeof(jqXHR.status) != 'undefined' && jqXHR.status == 0) {
            return;
        }

		try {
			var response = jQuery.parseJSON(jqXHR.responseText);
			if (response.error) {
				showErrorMessage(response.error);
			}
		}
		catch (e) {
            logDebug('[onMessageArrivalError] ' + jqXHR.responseText);
            logDebug('[errorThrown] ' + errorThrown);
			showErrorMessage('Server error occurred: ' + errorThrown);
		}
	};

	function onMessageSent(result) {
		setIdleState();
		try {
			var response = jQuery.parseJSON(result);
			if (response.error) {
				showErrorMessage(response.error);
			} else {
				checkNewMessages();
			}
		}
		catch (e) {
            logDebug('[onMessageSent] ' + result);
			showErrorMessage('Unknown error occurred: ' + e.toString());
		}
	};

	function onMessageSentError(jqXHR, textStatus, errorThrown) {
		setIdleState();

        if (typeof(jqXHR.status) != 'undefined' && jqXHR.status == 0) {
            showErrorMessage('No network connection');
            return;
        }

		try {
			var response = jQuery.parseJSON(jqXHR.responseText);
			if (response.error) {
				showErrorMessage(response.error);
			} else {
				showErrorMessage('Unknown server error occurred: ' + errorThrown);
			}
		}
		catch (e) {
            logDebug('[onMessageSentError] ' + jqXHR.responseText);
            logDebug('[errorThrown] ' + errorThrown);
			showErrorMessage('Server error occurred: ' + errorThrown);
		}
	};

	function sendMessageRequest(message, channelId, attachments) {
		setBusyState(attachments.length > 0);
		jQuery.ajax({
			type: "POST",
			url: messageEndpoint,
			data: {
				attachments: attachments,
                channelId: channelId,
				message: message,
				checksum: options.checksum
			},
			progressUpload: function(evt) {
				if (evt.lengthComputable) {
					var percent = parseInt(evt.loaded /evt.total * 100);
					if (percent > 100) {
						percent = 100;
					}
					progressBar.setValue(percent);
				}
			}
		})
		.done(onMessageSent)
		.fail(onMessageSentError);
	};

	function sendMessage() {
		var message = messagesInput.val().replace(/^\s+|\s+$/g, '');
        if (message == '[debug]') {
            showDebug();
            messagesInput.val('');
            return;
        }

		var attachments = messageAttachments.getAttachments();
		messageAttachments.clearAttachments();

		if (message.length > 0 || attachments.length > 0) {
			sendMessageRequest(message, channelId, attachments);

			messagesInput.val('');

			if (!isMessageMultiline) {
				switchToSingle();
			} else {
				fitMultilineTextInput();
			}

			if (!isMessageMultiline && message.length > 0) {
				messagesHistory.resetPointer();
				if (messagesHistory.getPreviousMessage() != message) {
					messagesHistory.addMessage(message);
				}
				messagesHistory.resetPointer();
			}
		}
	};

    function showDebug() {
        var debugContainer = container.find('.wcDebug');
        if (debugContainer.length == 0) {
            debugContainer = jQuery('<div>').attr('class', 'wcDebug');
            container.append(jQuery('<div>').html('  [ DEBUG MODE error log. Select and copy the text below: ]  '));
            container.append(debugContainer);
        }

		updateDebugLog();
    }

	function updateDebugLog() {
		var debugContainer = container.find('.wcDebug');
		if (debugContainer.length > 0 && debugLog.length < 20) {
			debugContainer.html(debugLog.join('<br />'));
		}
	}

	function switchToMultiline() {
		// check if it was executed already:
		if (messagesInput.is('textarea')) {
			return;
		}

		// create new textarea and put a message into it:
		var textarea = jQuery('<textarea />');
		textarea.hide();
		textarea.addClass('wcInput');
		textarea.attr('placeholder', options.messages.hint_message);
		textarea.attr('maxlength', options.messageMaxLength);
		textarea.css('overflow-y', 'hidden');
		textarea.keypress(onInputKeyPress);
		textarea.val(messagesInput.val() + "\n");

		// remove single-lined input:
		messagesInput.after(textarea);
		messagesInput.off('keypress');
		messagesInput.off('keydown');
		messagesInput.remove();

		textarea.show();
		textarea.focus();
		messagesInput = textarea;
	}

	function switchToSingle() {
		// check if it was executed already:
		if (messagesInput.is('input')) {
			return;
		}

		// create new textarea and put a message into it:
		var input = jQuery('<input />');
		input.hide();
		input.addClass('wcInput');
		input.attr('placeholder', options.messages.hint_message);
		input.attr('maxlength', options.messageMaxLength);
		input.attr('type', 'text');
		input.attr('title', options.messages.messageInputTitle);
		input.keypress(onInputKeyPress);
		input.keydown(onInputKeyDown);

		// remove single-lined input:
		messagesInput.after(input);
		messagesInput.off('keypress');
		messagesInput.remove();

		input.show();
		input.focus();
		messagesInput = input;

		// Safari fix - unknown new line appears, should be cleared:
		setTimeout(function () {
			messagesInput.val('');
		}, 200);
	}

	function fitMultilineTextInput() {
		var lines = messagesInput.val().replace(/^\s+|\s+$/g, '').split("\n").length;
		var lineHeight = parseInt(messagesInput.css('line-height'));
		lines++;
		if (lines > 0 && !isNaN(lineHeight) && lineHeight > 0) {
			messagesInput.css('height', (lines * lineHeight + 10) + 'px');
		}
	}

	function onInputKeyPress(e) {
		if (e.which == 13) {
			if (e.shiftKey) {
				if (!isMessageMultiline) {
					switchToMultiline();
				}
				fitMultilineTextInput();

				// move cusor to the end:
				if (!isMessageMultiline) {
					messagesInput.focus();
					var text = messagesInput.val();
					messagesInput.val('');
					messagesInput.val(text);
					messagesInput.focus();
				}
			} else {
				sendMessage();
			}
		}
	};

	function onInputKeyDown(e) {
		if (!isMessageMultiline) {
			var keyCode = e.which;
			var messageCandidate = null;

			if (keyCode == 38) {
				messageCandidate = messagesHistory.getPreviousMessage();
			} else if (keyCode == 40) {
				messageCandidate = messagesHistory.getNextMessage();
			}
			if (messageCandidate !== null) {
				messagesInput.val(messageCandidate);
			}
		}
	};

	function refreshUsersList(data) {
		usersListContainer.html(data);
	}

	function refreshUsersCounter(data) {
		var total = data.total > 0 ? data.total : 1;
		if (options.channelUsersLimit > 0) {
			usersCounter.html(total + " / " + options.channelUsersLimit);
		} else {
			usersCounter.html(total);
		}
	}

	function setMessagesProperty(data) {
		container.find('div[data-chat-user-id="' + data.chatUserId + '"]').each(function(index, element) {
			if (data.propertyName == 'textColor') {
				var cssSelector = '.wcMessageUser, .wcMessageUser a, .wcMessageContent, .wcMessageTime';
				jQuery(element).find(cssSelector).css({color: data.propertyValue});
			}
		});
	}

	function onWindowResize() {
		if (container.width() < 300) {
			container.addClass('wcWidth300');
		} else {
			container.removeClass('wcWidth300');
		}

		if (options.showUsersList && options.autoHideUsersList) {
			if (container.width() < options.autoHideUsersListWidth) {
				container.removeClass('wcUsersListIncluded');
				usersListContainer.hide();
				usersListContainer.next('.wcClear').hide();
			} else {
				container.addClass('wcUsersListIncluded');
				usersListContainer.show();
				usersListContainer.next('.wcClear').show();
			}
		}
	}

	function onMessageDelete() {
		if (!confirm('Are you sure you want to delete this message?')) {
			return;
		}

		var deleteButton = jQuery(this);
		var messageId = deleteButton.data('id');
		jQuery.ajax({
			type: "POST",
			url: messageDeleteEndpoint,
			data: {
                channelId: channelId,
				messageId: messageId,
				checksum: options.checksum
			}
		})
		.done(function() {
			hideMessage(messageId);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
            logDebug('[onMessageDelete] ' + jqXHR.responseText);
			showErrorMessage('Server error: ' + errorThrown);
		});
	}

	function onUserBan() {
		if (!confirm('Are you sure you want to ban this user?')) {
			return;
		}

		var messageId = jQuery(this).data('id');
		jQuery.ajax({
			type: "POST",
			url: userBanEndpoint,
			data: {
                channelId: channelId,
				messageId: messageId,
				checksum: options.checksum
			}
		})
		.done(function(result) {
			try {
				var response = jQuery.parseJSON(result);
				if (response.error) {
					showErrorMessage(response.error);
				}
			}
			catch (e) {
				showErrorMessage('Server error: ' + e.toString());
			}
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
            logDebug('[onUserBan] ' + jqXHR.responseText);
			showErrorMessage('Server error occurred: ' + errorThrown);
		});
	}

    function onUserNameClick() {
        appendTextToInputField('@' + jQuery(this).text() + ': ');
    }
	
	function attachEventListeners() {
		container.on('click', 'a.wcMessageDeleteButton', onMessageDelete);
		container.on('click', 'a.wcUserBanButton', onUserBan);
        container.on('click', 'a.wcMessageUserReplyTo', onUserNameClick);
    }

    // DOM events:
	messagesInput.keypress(onInputKeyPress);
    messagesInput.keydown(onInputKeyDown);
    submitButton.click(sendMessage);
    jQuery(window).resize(onWindowResize);

	// public API:
	this.start = function() {
		initializeRefresher();
		dateAndTimeRenderer.convertUTCMessagesTime(container, options.nowTime);
		onWindowResize();
		attachEventListeners();
		scrollMessages();
		if (options.debugMode) {
			showDebug();
		}
	};
	
	this.scrollMessages = scrollMessages;
	this.showErrorMessage = showErrorMessage;
	this.showPlainMessage = showPlainMessage;
	this.hideMessage = hideMessage;
	this.hideAllMessages = hideAllMessages;
	this.refreshUsersList = refreshUsersList;
	this.refreshUsersCounter = refreshUsersCounter;
	this.replaceUserNameInMessages = replaceUserNameInMessages;
	this.refreshPlainUserName = refreshPlainUserName;
	this.setMessagesProperty = setMessagesProperty;
    this.appendTextToInputField = appendTextToInputField;
    this.logDebug = logDebug;
};