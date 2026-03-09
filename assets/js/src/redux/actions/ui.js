import ChannelsStorage from "utils/channels-storage";
import {sendUserCommand} from "./commands";

export function requestChannelOpening(channelId) {
	return {
		type: 'ui.channel.opening.request',
		channelId: channelId
	}
}

export function clearChannelOpeningRequest() {
	return {
		type: 'ui.channel.opening.request.clear'
	}
}

export function focusChannel(channelId) {
	return function(dispatch, getState) {
		const channelsStorage = new ChannelsStorage(getState().application.user.cacheId);
		channelsStorage.markFocused(channelId);

		dispatch({
			type: 'ui.channel.focus',
			id: channelId
		});
		dispatch(unreadClear(channelId));
	}
}

export function openChannel(channelId, openFirst = false) {
	return function(dispatch, getState, advanced) {
		let x = 6;
		let counter = 10;
		const limit = counter - 2 - x;
		if (getState().ui.openedChannels.length > limit) {
			dispatch(alertInfo('You can open no more than 3 channels at the same time'));
			return;
		}

		sendUserCommand('openChannel', 'openChannel', { channelId: channelId, openFirst: openFirst })(dispatch, getState, advanced);
		dispatch({
			type: 'ui.channel.open',
			id: channelId,
			openFirst: openFirst
		});
	}
}

export function ignoreChannel(channelId) {
	return function(dispatch, getState) {
		const channelsStorage = new ChannelsStorage(getState().application.user.cacheId);
		channelsStorage.markIgnored(channelId);

		dispatch({
			type: 'ui.channel.ignore',
			id: channelId
		});
	}
}

/**
 * @requires getState().application.user
 */
export function restoreChannels() {
	return function(dispatch, getState) {
		const state = getState();
		const configuration = state.configuration;
		const channelsStorage = new ChannelsStorage(state.application.user ? state.application.user.cacheId : 'na');
		let openChannelsIDs = state.application.user.openChannels;

		dispatch({
			type: 'ui.channel.ignore.multiple',
			channels: channelsStorage.getIgnoredChannels()
		});
		dispatch({
			type: 'ui.channel.minimize.multiple',
			channels: channelsStorage.getHiddenChannels()
		});

		if (configuration.interface.browser.autoOpen) {
			const openedChannels = state.application.channels.filter( channel => state.application.user.openChannels.includes(channel.id) );
			const autoOpenChannels = state.application.autoOpenChannels;
			if (autoOpenChannels.length > 0) {
				const autoOpenChannelsIDs = autoOpenChannels.map( channel => channel.id );
				const onlineChannels = autoOpenChannels.filter( channel => channel.online === true );
				let candidate;
				if (onlineChannels.length > 0) {
					candidate = onlineChannels[Math.floor(Math.random() * onlineChannels.length)];
				} else {
					candidate = autoOpenChannels[Math.floor(Math.random() * autoOpenChannels.length)];
				}
				if (openedChannels.length === 0) {
					dispatch(openChannel(candidate.id));
					dispatch(focusChannel(candidate.id));
				} else {
					const openedAutoOpenChannel = autoOpenChannels.find( channel => openedChannels.includes(channel.id) );

					if (openedAutoOpenChannel) {
						dispatch({
							type: 'ui.channel.open.only',
							id: openedAutoOpenChannel.id
						});
						dispatch(focusChannel(openedAutoOpenChannel.id));
					} else {
						dispatch({
							type: 'ui.channel.open.only',
							id: candidate.id
						});
						dispatch(focusChannel(candidate.id));
					}
				}
				return;
			}
		} else if (configuration.interface.browser.autoOpenFirstPublic) {
			// in case no opened channel open the first public channel:
			const publicChannels = state.application.browserChannels.filter( channel => channel.type === 'public' );
			const openedChannels = state.application.channels.filter( channel => state.application.user.openChannels.includes(channel.id) );
			if ((openedChannels.length === 0 || configuration.interface.browser.autoOpenOnlyExisting) && publicChannels.length > 0) {
				dispatch(openChannel(publicChannels[0].id));
				dispatch(focusChannel(publicChannels[0].id));
				if (configuration.mode === 1 && configuration.interface.chat.fb.minimizeOnStart) {
					dispatch(minimizeChannel(publicChannels[0].id));
				}
				return;
			}
		}

		dispatch({
			type: 'ui.channel.open.multiple',
			channels: openChannelsIDs
		});

		let focusResolved = false;
		const focusedChannelId = channelsStorage.getFocused();
		if (focusedChannelId) {
			const focusedChannel = getState().application.channels.find( channel => channel.id === focusedChannelId );
			if (focusedChannel) {
				dispatch(focusChannel(focusedChannelId));
				focusResolved = true;
			}
		}

		if (!focusResolved) {
			// open the last existing channel:
			const lastExistingOpenedChannel = [ ...openChannelsIDs ]
				.reverse()
				.find( openedChannelId => getState().application.channels.find( channel => channel.id === openedChannelId ) );
			if (lastExistingOpenedChannel) {
				dispatch(focusChannel(lastExistingOpenedChannel));
			}
		}
	}
}

export function completeInit() {
	return {
		type: 'ui.init.complete'
	};
}

export function closeChannel(channelId) {
	return function(dispatch, getState, advanced) {
		sendUserCommand('closeChannel', 'closeChannel', { channelId: channelId })(dispatch, getState, advanced);

		dispatch({
			type: 'ui.channel.close',
			id: channelId
		});
	}
}

export function minimizeChannel(channelId) {
	return function(dispatch, getState) {
		const channelsStorage = new ChannelsStorage(getState().application.user ? getState().application.user.cacheId : 'na');
		channelsStorage.markHidden(channelId);

		dispatch({
			type: 'ui.channel.minimize',
			id: channelId
		});
	}
}

export function minimizeChannels(channelIds) {
	return function(dispatch, getState) {
		const channelsStorage = new ChannelsStorage(getState().application.user ? getState().application.user.cacheId : 'na');
		channelIds.map( channelId => channelsStorage.markHidden(channelId) );

		dispatch({
			type: 'ui.channel.minimize.multiple',
			channels: channelIds
		});
	}
}

export function maximizeChannel(channelId) {
	return function(dispatch, getState) {
		const channelsStorage = new ChannelsStorage(getState().application.user ? getState().application.user.cacheId : 'na');
		channelsStorage.unmarkHidden(channelId);

		dispatch({
			type: 'ui.channel.maximize',
			id: channelId
		});
	}
}

export function stopIgnoringChannel(channelId) {
	return function(dispatch, getState) {
		const channelsStorage = new ChannelsStorage(getState().application.user.cacheId);
		channelsStorage.clearIgnored(channelId);

		dispatch({
			type: 'ui.channel.ignore.stop',
			id: channelId
		});
	}
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

export function confirm(text, callback, cancelCallback, buttons, configuration = { title: '', timeout: undefined, className: '' }) {
	return {
		type: 'ui.confirm',
		text: text,
		callback: callback,
		cancelCallback: cancelCallback,
		buttons: buttons,
		configuration: configuration
	}
}

export function clearConfirm() {
	return {
		type: 'ui.confirm.clear'
	}
}

export function setMessageEditable(messageId) {
	return {
		type: 'ui.message.edit.start',
		messageId: messageId
	}
}

export function cancelMessageEditable(messageId) {
	return {
		type: 'ui.message.edit.cancel',
		messageId: messageId
	}
}

export function setMessageReplyTo(messageId, channel) {
	return {
		type: 'ui.message.reply-to.start',
		messageId: messageId,
		channel: channel
	}
}

export function cancelMessageReplyTo(channel) {
	return {
		type: 'ui.message.reply-to.cancel',
		channel: channel
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

export function unreadAdd(channelId, quantity) {
	return {
		type: 'ui.channel.unread.add',
		id: channelId,
		quantity: quantity
	}
}

export function unreadClear(channelId) {
	return {
		type: 'ui.channel.unread.clear',
		id: channelId
	}
}

export function setChannelProperty(channelId, name, value) {
	return {
		type: 'ui.channel.property.set',
		id: channelId,
		name: name,
		value: value
	}
}

export function logOff() {
	return {
		type: 'ui.log.off'
	}
}

export function destroy() {
	return {
		type: 'ui.destroy'
	}
}

export function clear() {
	return {
		type: 'ui.clear'
	}
}

export function requestStream(data) {
	return {
		type: 'ui.stream.request',
		data: data
	}
}

export function openStream(id, type, data) {
	return {
		type: 'ui.stream.open',
		data: { id: id, type: type, ...data }
	}
}

export function closeStream(id) {
	return {
		type: 'ui.stream.close',
		id: id
	}
}

export function openTwilioRoom(room) {
	return {
		type: 'ui.stream.twilio.room.open',
		data: room
	}
}

export function closeTwilioRoom() {
	return {
		type: 'ui.stream.twilio.room.close'
	}
}

export function updateProperties(properties) {
	return {
		type: 'ui.properties.update',
		data: properties
	}
}