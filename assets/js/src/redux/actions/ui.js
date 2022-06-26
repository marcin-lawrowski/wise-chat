export function focusChannel(channelId) {
	return function(dispatch) {
		dispatch({
			type: 'ui.channel.focus',
			id: channelId
		});
	}
}

export function openChannel(channelId) {
	return function(dispatch) {
		dispatch({
			type: 'ui.channel.open',
			id: channelId
		});
	}
}

export function completeInit() {
	return {
		type: 'ui.init.complete'
	};
}

export function appendToChannelInput(channelId, text) {
	return {
		type: 'ui.channel.input.append',
		id: channelId,
		text: text
	}
}

export function alertError(text) {
	return {
		type: 'ui.alert.error',
		text: text
	}
}

export function alertInfo(text) {
	return {
		type: 'ui.alert.info',
		text: text
	}
}

export function clearAlerts() {
	return {
		type: 'ui.alerts.clear'
	}
}

export function toastError(text) {
	return {
		type: 'ui.toast.error',
		text: text
	}
}

export function toastInfo(text) {
	return {
		type: 'ui.toast.info',
		text: text
	}
}

export function clearToasts() {
	return {
		type: 'ui.toasts.clear'
	}
}

export function confirm(text, callback, cancelCallback, buttons) {
	return {
		type: 'ui.confirm',
		text: text,
		callback: callback,
		cancelCallback: cancelCallback,
		buttons: buttons
	}
}

export function clearConfirm() {
	return {
		type: 'ui.confirm.clear'
	}
}

export function setMobileTopTab(tab) {
	return {
		type: 'ui.mobile.toptab',
		tab: tab
	}
}

export function setMobileTitle(title) {
	return {
		type: 'ui.mobile.title',
		title: title
	}
}

export function notify(eventName) {
	return {
		type: 'ui.notification.add',
		notification: {
			event: eventName
		}
	}
}

export function clearNotifications() {
	return {
		type: 'ui.notifications.clear'
	}
}