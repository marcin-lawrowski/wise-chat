/**
 * Wise Chat maintenance services.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatMaintenanceExecutor(options, wiseChatMessages, notifier) {
	var REFRESH_TIMEOUT = 10000;
	var ENDPOINT_URL = options.apiEndpointBase + '?action=wise_chat_maintenance_endpoint';
	var lastActionId = options.lastActionId;
	var isInitialized = false;
	var request = null;
	var actionsIdsCache = {};
	
	function initialize() {
		if (isInitialized == true) {
			return;
		}
		isInitialized = true;
		performMaintenanceRequest();
		setInterval(performMaintenanceRequest, REFRESH_TIMEOUT);
	};
	
	function isRequestStillRunning() {
		return request !== null && request.readyState > 0 && request.readyState < 4;
	}

	function onMaintenanceRequestError(jqXHR, textStatus, errorThrown) {
        // network problems ignore:
        if (typeof(jqXHR.status) != 'undefined' && jqXHR.status == 0) {
            return;
        }

		try {
			var response = jQuery.parseJSON(jqXHR.responseText);
			if (response.error) {
				wiseChatMessages.showErrorMessage('Maintenance error: ' + response.error);
			} else {
				wiseChatMessages.showErrorMessage('Unknown maintenance error: ' + errorThrown);
			}
		}
		catch (e) {
            wiseChatMessages.logDebug('[onMaintenanceRequestError] ' + jqXHR.responseText);
            wiseChatMessages.logDebug('[errorThrown] ' + errorThrown);
			wiseChatMessages.showErrorMessage('Maintenance fatal error: ' + errorThrown);
		}
	};
	
	function performMaintenanceRequest() {
		if (isRequestStillRunning()) {
			return;
		}
		
		request = jQuery.ajax({
			url: ENDPOINT_URL,
			data: {
				lastActionId: lastActionId,
                channelId: options.channelId,
				checksum: options.checksum
			}
		})
		.done(analyzeResponse)
		.fail(onMaintenanceRequestError);
	};
	
	function analyzeResponse(data) {
		try {
			var maintenance = jQuery.parseJSON(data);
			
			if (typeof(maintenance.actions) !== 'undefined') {
				executeActions(maintenance.actions);
			}
			if (typeof(maintenance.events) !== 'undefined') {
				handleEvents(maintenance.events);
			}
			if (typeof(maintenance.error) !== 'undefined') {
				wiseChatMessages.showErrorMessage('Maintenance error occurred: ' + maintenance.error);
			}
		}
		catch (e) {
            wiseChatMessages.logDebug('[analyzeResponse] ' + data);
			wiseChatMessages.showErrorMessage('Maintenance corrupted data: ' + e.message);
		}
	};
	
	function executeActions(actions) {
		for (var x = 0; x < actions.length; x++) {
			var action = actions[x];
			var actionId = action.id;
			var commandName = action.command.name;
			var commandData = action.command.data;
			if (actionId > lastActionId) {
				lastActionId = actionId;
			}
			
			if (!actionsIdsCache[actionId]) {
				actionsIdsCache[actionId] = true;
				
				switch (commandName) {
					case 'deleteMessage':
						wiseChatMessages.hideMessage(commandData.id);
						break;
					case 'deleteMessages':
						deleteMessagesAction(commandData);
						break;
					case 'deleteAllMessagesFromChannel':
						if (commandData.channelId == options.channelId) {
							wiseChatMessages.hideAllMessages();
						}
						break;
					case 'deleteAllMessages':
						wiseChatMessages.hideAllMessages();
						break;
					case 'replaceUserNameInMessages':
						wiseChatMessages.replaceUserNameInMessages(commandData.renderedUserName, commandData.messagesIds);
						break;
					case 'refreshPlainUserName':
						wiseChatMessages.refreshPlainUserName(commandData.name);
						break;
					case 'showErrorMessage':
						wiseChatMessages.showErrorMessage(commandData.message);
						break;
					case 'setMessagesProperty':
						wiseChatMessages.setMessagesProperty(commandData);
						break;
				}
			}
		}
	};
	
	function handleEvents(events) {
		for (var x = 0; x < events.length; x++) {
			var event = events[x];
			var eventData = event.data;
			
			switch (event.name) {
				case 'refreshUsersList':
					wiseChatMessages.refreshUsersList(eventData);
					break;
				case 'refreshUsersCounter':
					wiseChatMessages.refreshUsersCounter(eventData);
					break;
				case 'userData':
					options.userData = eventData;
					break;
				case 'reportAbsentUsers':
					if (jQuery.isArray(eventData.users) && eventData.users.length > 0) {
						if (options.enableLeaveNotification) {
							for (var y = 0; y < eventData.users.length; y++) {
								var user = eventData.users[y];
								wiseChatMessages.showPlainMessage(user.name + ' ' + options.messages.messageHasLeftTheChannel);
							}
						}
						if (options.leaveSoundNotification && eventData.users.length > 0) {
							notifier.sendNotificationForEvent('userLeft');
						}
					}
					break;
				case 'reportNewUsers':
					if (jQuery.isArray(eventData.users) && eventData.users.length > 0) {
						if (options.enableJoinNotification) {
							for (var y = 0; y < eventData.users.length; y++) {
								var user = eventData.users[y];
								wiseChatMessages.showPlainMessage(user.name + ' ' + options.messages.messageHasJoinedTheChannel);
							}
						}
						if (options.joinSoundNotification && eventData.users.length > 0) {
							notifier.sendNotificationForEvent('userJoined');
						}
					}
					break;
			}
		}
	};
	
	function deleteMessagesAction(data) {
		for (var x = 0; x < data.ids.length; x++) {
			wiseChatMessages.hideMessage(data.ids[x]);
		}
	};
	
	// public API:
	this.start = initialize;
};