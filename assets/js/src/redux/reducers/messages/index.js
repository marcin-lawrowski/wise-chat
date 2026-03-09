import mergeMessages from "utils/messages";

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

			Object.keys(grouped).map( channelId => grouped[channelId] = mergeMessages(grouped[channelId]) );

			return createState(state, { received: { ...state.received, ...grouped } });
		case 'message.receive.past':

			return createState(state, {
				receivedPast: { ...state.receivedPast, [action.channelId]: Object.assign({}, state.receivedPast[action.channelId], action.data) }
			});
		case 'message.receive.past.done':
			const mergedMessages = state.received[action.channelId] ? mergeMessages([ ...state.received[action.channelId], ...action.data ]) : action.data;

			return createState(state, {
				received: { ...state.received, [action.channelId]: mergedMessages }
			});
		case 'message.image':
			return createState(state, { image: { ...state.image, [action.id]: Object.assign({}, state.image[action.id], action.data) } });
		case 'message.delete':
			const deleteMessageChannelId = Object.keys(state.received).find( channelId => state.received[channelId].find( message => message.id === action.id ) );

			return deleteMessageChannelId
				? createState(state, { received: { ...state.received, [deleteMessageChannelId]: state.received[deleteMessageChannelId].filter( message => message.id !== action.id ) } })
				: state;
		case 'message.delete.all.from.channel':
			return state.received[action.channelId]
				? createState(state, { received: { ...state.received, [action.channelId]: [] } })
				: state;
		case 'message.delete.multiple':
			const newReceived = {};

			Object.keys(state.received).forEach( channelId => {
				newReceived[channelId] = state.received[channelId].filter( message => !action.ids.includes(message.id) );
			});

			return createState(state, { received: newReceived });
		case 'message.replace':
			return state.received[action.message.channel.id]
				? createState(state, { received: { ...state.received, [action.message.channel.id]: state.received[action.message.channel.id].map( message => message.id !== action.message.id ? message : { ...action.message } ) } })
				: state;
		case 'messages.replace.all':
			return createState(state, { received: { ...state.received, [action.channelId]: action.messages } });
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
		case 'message.reactions.counters.replace':
			const newReactionsAlteredReceived = {};

			Object.keys(state.received).forEach( channelId => {
				newReactionsAlteredReceived[channelId] = state.received[channelId].map( message => {
					return message.id === action.id ? { ...message, reactions: action.reactions } : message;
				});
			});

			return createState(state, { received: newReactionsAlteredReceived });
		case 'messages.clear':
			return createState(state, defaultState);
		default:
			return state
	}
}