export function sendMessage(content, attachments, customParameters, channelId) {

	return function(dispatch, getState, {engine, configuration}) {
		dispatch({ type: "message.send", id: channelId, data: { inProgress: true, progress: 0, error: undefined, result: undefined, success: undefined } });

		engine.sendMessage({
				content: content,
				attachments: attachments,
				customParameters: customParameters,
				channelId: channelId,
				checksum: configuration.checksum
			},
			(result) => {
				dispatch({ type: "message.send", id: channelId, data: { inProgress: false, success: true, progress: 100, result: result } });
			},
			(progress) => {
				dispatch({ type: "message.send", id: channelId, data: { progress: progress } });
			},
			(error) => {
				dispatch({ type: "message.send", id: channelId, data: { inProgress: false, success: false, error: error } });
			}
		);
	}

}

export function receive(messages) {
	return function(dispatch) {
		dispatch({
			type: "message.receive",
			messages: messages
		});
	}
}

export function loadPastMessages(channelId, beforeMessage = undefined) {
	return function(dispatch, getState, {engine, configuration}) {
		dispatch({ type: "message.receive.past", channelId: channelId, beforeMessage: beforeMessage, data: { inProgress: true, error: undefined, result: undefined, success: undefined } });

		engine.loadPastMessages(channelId, beforeMessage,
			(result) => {
				dispatch({ type: "message.receive.past.done", channelId: channelId, beforeMessage: beforeMessage, data: result });
				dispatch({ type: "message.receive.past", channelId: channelId, beforeMessage: beforeMessage, data: { inProgress: false, success: true, result: result } });
			},
			(error) => {
				dispatch({ type: "message.receive.past", channelId: channelId, beforeMessage: beforeMessage, data: { inProgress: false, success: false, error: error } });
			}
		);
	}
}

export function clearLoadPastMessages(channelId) {
	return function(dispatch) {
		dispatch({ type: "message.receive.past.clear", id: channelId });
	}
}

export function deleteMessage(id, channel) {
	return function(dispatch) {
		dispatch({
			type: "message.delete",
			id: id,
			channel: channel
		});
	}
}

export function deleteMessages(ids) {
	return function(dispatch) {
		dispatch({
			type: "message.delete.multiple",
			ids: ids
		});
	}
}

export function refreshSender(id, name) {
	return function(dispatch) {
		dispatch({
			type: "messages.sender.replace",
			id: id,
			name: name
		});
	}
}

export function prepareImage(data, channelId) {
	return function(dispatch, getState, {engine, configuration}) {
		dispatch({ type: "message.image", id: channelId, data: { inProgress: true, progress: 0, error: undefined, result: undefined, success: undefined } });

		engine.prepareImage(data,
			(result) => {
				dispatch({ type: "message.image", id: channelId, data: { inProgress: false, success: true, progress: 100, result: result } });
			},
			(progress) => {
				dispatch({ type: "message.image", id: channelId, data: { progress: progress } });
			},
			(error) => {
				dispatch({ type: "message.image", id: channelId, data: { inProgress: false, success: false, error: error } });
			}
		);
	}
}