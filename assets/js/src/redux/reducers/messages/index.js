const defaultState = {
	posted: {},
	received: {},
	receivedPast: {},
	image: {}
}

export default function messages(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'message.send':
			return createState(state, { posted: { ...state.posted, [action.id]: Object.assign({}, state.posted[action.id], action.data) } })
		case 'message.receive':
			let grouped = {};
			for (let x = 0; x < action.messages.length; x++) {
				const message = action.messages[x];

				if (!grouped[message.channel.id]) {
					if (state.received[message.channel.id]) {
						grouped[message.channel.id] = [...state.received[message.channel.id]];
					} else {
						grouped[message.channel.id] = [];
					}
				}

				grouped[message.channel.id].push(message);
			}

			return createState(state, { received: { ...state.received, ...grouped } });
		case 'message.receive.past':

			return createState(state, {
				receivedPast: { ...state.receivedPast, [action.channelId]: Object.assign({}, state.receivedPast[action.channelId], action.data) }
			});
		case 'message.receive.past.done':
			const mergedMessages = state.received[action.channelId] ? [ ...state.received[action.channelId], ...action.data ] : action.data;

			// remove duplicates:
			const uniqueMessages = [...mergedMessages.reduce((p, c) => p.set(c.id, c), new Map())].map(([key, value]) => value);

			// sort:
			const sortedMessages = uniqueMessages.sort((a, b) => {
				if (a.sortKey < b.sortKey) {
					return -1;
				}
				if (a.sortKey > b.sortKey) {
					return 1;
				}

				return 0;
			});

			return createState(state, {
				received: { ...state.received, [action.channelId]: sortedMessages }
			});
		case 'message.image':
			return createState(state, { image: { ...state.image, [action.id]: Object.assign({}, state.image[action.id], action.data) } });
		case 'message.delete':
			return state.received[action.channel]
				? createState(state, { received: { ...state.received, [action.channel]: state.received[action.channel].filter( message => message.id !== action.id ) } })
				: state;
		case 'message.delete.multiple':
			const newReceived = {};

			Object.keys(state.received).forEach( channelId => {
				newReceived[channelId] = state.received[channelId].filter( message => !action.ids.includes(message.id) );
			});

			return createState(state, { received: newReceived });
		case 'messages.sender.replace':
			const newSenderAlteredReceived = {};

			Object.keys(state.received).forEach( channelId => {
				newSenderAlteredReceived[channelId] = state.received[channelId].map( message => {
					return message.sender.id === action.id
						? { ...message, sender: { ...message.sender, name: action.name } }
						: message;
				});
			});

			return createState(state, { received: newSenderAlteredReceived });
		default:
			return state
	}
}