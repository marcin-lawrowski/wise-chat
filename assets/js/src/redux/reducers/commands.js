const defaultState = {
	sent: {}
}

export default function commands(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'command.send':
			return createState(state, { sent: { ...state.sent, [action.id]: {...action.data} } });
		case 'command.clear':
			return createState(state, { sent: { ...state.sent, [action.id]: undefined } });
		default:
			return state
	}
}