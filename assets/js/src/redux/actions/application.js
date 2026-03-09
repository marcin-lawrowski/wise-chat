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

export function markFeedEntryAsSeen(userFeedEntryId) {
	return {
		type: 'application.feed.read',
		data: userFeedEntryId
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

export function addIncomingChat(incomingChat) {
	return {
		type: 'application.incoming.add',
		data: incomingChat
	}
}

export function deleteIncomingChats(channelsIDs) {
	return {
		type: 'application.incoming.delete',
		data: channelsIDs
	}
}

export function refreshChannel(channel) {
	return {
		type: 'application.channel.replace',
		channel: channel
	}
}

export function clear() {
	return {
		type: 'application.clear'
	}
}

export function addChannel(channel, storageOnly = false) {
	return {
		type: 'application.channel.add',
		channel: channel,
		storageOnly: storageOnly
	}
}

export function removeChannel(channelId) {
	return {
		type: 'application.channel.remove',
		channelId: channelId
	}
}

export function mergeFeed(feedInput) {
	return {
		type: 'application.userFeed.merge',
		data: feedInput
	}
}