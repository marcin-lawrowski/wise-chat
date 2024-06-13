export function updateData(dataName, data) {
	return {
		type: dataName,
		data: data
	}
}

export function heartBeat(data) {
	return {
		type: 'application.heartbeat',
		data: data
	}
}

export function markRecentChatRead(channel) {
	return {
		type: 'application.recent.read',
		data: channel
	}
}

export function refreshAuthenticationData() {
	return function(dispatch, getState, {engine}) {
		engine.triggerMaintenance();
	}
}

export function markChannelAuthorized(channelId) {
	return {
		type: 'application.channel.authorize',
		data: channelId
	}
}

export function updateDOMPresence(isPresent) {
	return {
		type: 'application.dom.present',
		data: isPresent
	}
}

/**
 * Detects new incoming direct messages and stores them in the store.
 * Usage: display new incoming chats either by opening them or displaying a confirmation dialog.
 *
 * @param {Array} messages
 * @returns {{data: *, type: string}}
 */
export function detectIncomingChats(messages) {
	return {
		type: 'application.incoming',
		data: messages
	}
}

export function deleteIncomingChats(channels) {
	return {
		type: 'application.incoming.delete',
		data: channels
	}
}

export function refreshChannel(channelId, name) {
	return {
		type: 'application.channel.replace',
		id: channelId,
		name: name
	}
}

export function clear() {
	return {
		type: 'application.clear'
	}
}

export function addChannel(channel) {
	return {
		type: 'application.channel.add',
		channel: channel
	}
}