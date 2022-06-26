const defaultState = {
	initiated: false, // when UI state has been restored / initiated
	openedChannels: [],
	focusedChannel: undefined,
	alerts: {
		error: undefined,
		info: undefined
	},
	toasts: [],
	notifications: [],
	confirms: undefined,
	channels: { },
	mobile: {
		topTab: 'chats',
		title: undefined
	}
}

export default function ui(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'ui.init.complete':
			return createState(state, { initiated: true });
		case 'ui.channel.focus':
			return createState(state, { focusedChannel: action.id });
		case 'ui.channel.open':
			return state.openedChannels.includes(action.id)
				? state
				: createState(state, { openedChannels: [ ...state.openedChannels, action.id ] } );
		case 'ui.channel.input.append':
			return createState(state, { channels: { ...state.channels, [action.id]: { ...state.channels[action.id], inputAppend: action.text } } } );
		case 'ui.channel.map':
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
			return createState(state, { confirms: { text: action.text, callback: action.callback, cancelCallback: action.cancelCallback, buttons: action.buttons } });
		case 'ui.confirm.clear':
			return createState(state, { confirms: undefined });
		case 'ui.mobile.toptab':
			return createState(state, { mobile: { ...state.mobile, topTab: action.tab } });
		case 'ui.mobile.title':
			return createState(state, { mobile: { ...state.mobile, title: action.title } });
		case 'ui.notification.add':
			return createState(state, { notifications: [ ...state.notifications, action.notification ] });
		case 'ui.notifications.clear':
			return createState(state, { notifications: [] });
		default:
			return state
	}
}