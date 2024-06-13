import ChannelsStorage from "utils/channels-storage";

const defaultState = {
	initiated: false, // when UI state has been restored / initiated
	channelOpeningRequest: undefined,
	openedChannels: [],
	ignoredChannels: [],
	minimizedChannels: [],
	focusedChannel: undefined,
	alerts: {
		error: undefined,
		info: undefined
	},
	toasts: [],
	notifications: [],
	confirms: undefined,
	channels: { },
	editableMessages: { },
	replyToMessages: { },
	mobile: {
		topTab: 'chats',
		title: undefined
	},
	logOffRequest: undefined,
	streams: [],
	streamRequest: undefined,
	twilio: {
		room: undefined
	},
	properties: {
		windowWidth: 1300,
		windowSizeClass: 'Xl',
		isMobile: false
	}
}

export default function ui(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'ui.init.complete':
			return createState(state, { initiated: true });
		case 'ui.channel.opening.request':
			return createState(state, { channelOpeningRequest: action.channelId });
		case 'ui.channel.opening.request.clear':
			return createState(state, { channelOpeningRequest: undefined });
		case 'ui.channel.focus':
			return createState(state, { focusedChannel: action.id });
		case 'ui.channel.open':
			return state.openedChannels.includes(action.id)
				? state
				: createState(state, { openedChannels: [ ...state.openedChannels, action.id ] } );
		case 'ui.channel.open.multiple':
			if (!Array.isArray(action.channels)) {
				return state;
			}
			const channelsToAdd = [...new Set(action.channels.filter( channel => !state.openedChannels.includes(channel)))];
			return createState(state, { openedChannels: [ ...state.openedChannels, ...channelsToAdd ] } );
		case 'ui.channel.minimize':
			return state.minimizedChannels.includes(action.id)
				? state
				: createState(state, { minimizedChannels: [ ...state.minimizedChannels, action.id ] } );
		case 'ui.channel.minimize.multiple':
			if (!Array.isArray(action.channels)) {
				return state;
			}
			const channelsToMinimize = [...new Set(action.channels.filter( channel => !state.minimizedChannels.includes(channel)))];
			return createState(state, { minimizedChannels: [ ...state.minimizedChannels, ...channelsToMinimize ] } );
		case 'ui.channel.maximize':
			return createState(state, { minimizedChannels: state.minimizedChannels.filter( channelId => channelId !== action.id) } );
		case 'ui.channel.ignore':
			return state.ignoredChannels.includes(action.id)
				? state
				: createState(state, { ignoredChannels: [ ...state.ignoredChannels, action.id ] } );
		case 'ui.channel.ignore.stop':
			return createState(state, { ignoredChannels: state.ignoredChannels.filter( channelId => channelId !== action.id) } )
		case 'ui.channel.ignore.multiple':
			if (!Array.isArray(action.channels)) {
				return state;
			}
			const ignoredChannels = [...new Set(action.channels.filter( channel => !state.ignoredChannels.includes(channel)))];
			return createState(state, { ignoredChannels: [ ...state.ignoredChannels, ...ignoredChannels ] } )
		case 'ui.channel.close':
			return createState(state, { openedChannels: state.openedChannels.filter( channelId => channelId !== action.id) } )
		case 'ui.channel.input.append':
			return createState(state, { channels: { ...state.channels, [action.id]: { ...state.channels[action.id], inputAppend: action.text } } } );
		case 'ui.channel.unread.add':
			const currentQuantity = state.channels[action.id] && state.channels[action.id].unread ? state.channels[action.id].unread : 0;

			return createState(state, {
				channels: { ...state.channels, [action.id]: { ...state.channels[action.id], unread: currentQuantity + action.quantity } }
			} );
		case 'ui.channel.unread.clear':
			return createState(state, {
				channels: { ...state.channels, [action.id]: { ...state.channels[action.id], unread: 0 } }
			} );
		case 'ui.channel.property.set':
			return createState(state, { channels: { ...state.channels, [action.id]: { ...state.channels[action.id], [action.name]: action.value } } } );
		case 'ui.channel.map':
			const channelsStorage = new ChannelsStorage(action.userCacheId);
			channelsStorage.mapChannel(action.from, action.to);

			return createState(state, {
				openedChannels: state.openedChannels.map( channelId => channelId === action.from ? action.to : channelId ),
				focusedChannel: state.focusedChannel === action.from ? action.to : state.focusedChannel
			});
		case 'ui.alert.error':
			return createState(state, { alerts: { ...state.alerts, error: action.text } });
		case 'ui.alert.info':
			return createState(state, { alerts: { ...state.alerts, info: action.text } });
		case 'ui.alerts.clear':
			return createState(state, { alerts: { error: undefined, info: undefined } });
		case 'ui.toast.error':
			return createState(state, { toasts: [ ...state.toasts, { type: 'error', text: action.text } ] });
		case 'ui.toast.info':
			return createState(state, { toasts: [ ...state.toasts, { type: 'info', text: action.text } ] });
		case 'ui.toasts.clear':
			return createState(state, { toasts: [] });
		case 'ui.confirm':
			return createState(state, { confirms: { text: action.text, callback: action.callback, cancelCallback: action.cancelCallback, buttons: action.buttons, configuration: action.configuration } });
		case 'ui.confirm.clear':
			return createState(state, { confirms: undefined });
		case 'ui.message.edit.start':
			return createState(state, { editableMessages: { ...state.editableMessages, [action.messageId]: true } });
		case 'ui.message.edit.cancel':
			return createState(state, { editableMessages: Object.keys(state.editableMessages).reduce((result, key) => {
					if (key !== action.messageId) {
						result[key] = state.editableMessages[key];
					}
					return result;
				}, {}) });
		case 'ui.message.reply-to.start':
			return createState(state, { replyToMessages: { ...state.replyToMessages, [action.channel]: action.messageId } });
		case 'ui.message.reply-to.cancel':
			return createState(state, { replyToMessages: Object.keys(state.replyToMessages).reduce((result, key) => {
					if (key !== action.channel) {
						result[key] = state.replyToMessages[key];
					}
					return result;
				}, {}) });
		case 'ui.mobile.toptab':
			return createState(state, { mobile: { ...state.mobile, topTab: action.tab } });
		case 'ui.mobile.title':
			return createState(state, { mobile: { ...state.mobile, title: action.title } });
		case 'ui.notification.add':
			return createState(state, { notifications: [ ...state.notifications, action.notification ] });
		case 'ui.notifications.clear':
			return createState(state, { notifications: [] });
		case 'ui.log.off':
			return createState(state, { logOffRequest: new Date() });
		case 'ui.clear':
			return createState(state, defaultState);
		case 'ui.stream.request':
			return createState(state, { streamRequest: action.data });
		case 'ui.stream.open':
			if (state.streams.find( stream => stream.id === action.data.id )) {
				return createState(state, { streams: state.streams.map( stream => stream.id === action.data.id ? action.data : stream ) });
			} else {
				return createState(state, { streams: [...state.streams, action.data] });
			}
		case 'ui.stream.close':
			return createState(state, { streams: state.streams.filter( stream => stream.id !== action.id ) } );
		case 'ui.stream.twilio.room.open':
			return createState(state, { twilio: { ...state.twilio, room: action.data } });
		case 'ui.stream.twilio.room.close':
			return createState(state, { twilio: { ...state.twilio, room: undefined } });
		case 'ui.properties.update':
			return createState(state, { properties: { ...state.properties, ...action.data} });
		default:
			return state
	}
}