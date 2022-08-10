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