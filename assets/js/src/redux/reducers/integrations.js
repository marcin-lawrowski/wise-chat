const defaultState = {
	tenor: {
		categories: {
			inProgress: false,
			results: []
		},
		search: {
			keyword: '',
			inProgress: false,
			results: []
		}
	}
}

export default function integrations(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'integrations.tenor.categories':
			return createState(state, { tenor: { ...state.tenor, categories: Object.assign({}, state.tenor.categories, action.data) } });
		case 'integrations.tenor.search':
			return createState(state, { tenor: { ...state.tenor, search: Object.assign({}, state.tenor.search, action.data) } });
		default:
			return state
	}
}