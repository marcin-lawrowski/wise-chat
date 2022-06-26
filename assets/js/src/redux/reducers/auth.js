const defaultState = {
	sent: {}
}

export default function auth(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'auth.send':
			return createState(state, { sent: { ...state.sent, [action.mode]: Object.assign({}, state.sent[action.mode], action.data) } })
		default:
			return state
	}
}