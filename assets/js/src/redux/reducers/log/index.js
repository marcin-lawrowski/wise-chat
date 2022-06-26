const defaultState = {
	entries: [ ]
}

export default function log(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'log.append':
			return createState(state, { entries: [ ...state.entries, action.payload ] });
		default:
			return state;
	}
}