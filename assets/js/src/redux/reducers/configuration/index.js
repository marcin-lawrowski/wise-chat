const defaultState = {
	windowTitle: ''
}

export default function configuration(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'replace':
			return createState(state, action.data)
		default:
			return state
	}
}