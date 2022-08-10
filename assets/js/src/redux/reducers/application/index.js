const defaultState = {
	domPresent: true,
	channels: [], // this is global channels storage (both those online and past / offline)
	publicChannels: [],
	directChannels: [],
	onlineUsersCounter: 0,
	channelMap: undefined,
	users: [],
	usersCounter: [],
	absentUsers: [],
	newUsers: [],
	userRights: {},
	user: undefined,
	auth: undefined,
	checkSum: undefined,
	actions: [],
	i18n: {},
	heartbeat: {
		nowTime: undefined
	}
}

export default function application(state = defaultState, action) {
	let createState = (oldState = state, adjustment) => {
		return Object.assign({}, oldState, adjustment)
	}

	switch (action.type) {
		case 'publicChannels':
			// add to global channels storage only if not present:
			const publicChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );

			return createState(state, {
				publicChannels: JSON.stringify(state.publicChannels) !== JSON.stringify(action.data) ? action.data : state.publicChannels,
				channels: publicChannelCandidates.length > 0 ? [...state.channels, ...publicChannelCandidates] : state.channels
			});
		case 'directChannels':
			// add to global channels storage only if not present:
			const directChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );

			return createState(state, {
				directChannels: JSON.stringify(state.directChannels) !== JSON.stringify(action.data) ? action.data : state.directChannels,
				channels: directChannelCandidates.length > 0 ? [...state.channels, ...directChannelCandidates] : state.channels
			});
		case 'users':
		case 'onlineUsersCounter':
		case 'usersCounter':
		case 'absentUsers':
		case 'newUsers':
		case 'userRights':
		case 'user':
		case 'auth':
		case 'checkSum':
		case 'actions':
		case 'i18n':
			return createState(state, { [action.type]: action.data })
		case 'application.channel.map':
			return createState(state, {
				channelMap: action,
				channels: state.channels.map( channel => {
					return channel.id === action.from ? { ...channel, id: action.to } : channel
				} )
			});
		case 'application.channel.authorize':
			return createState(state, {
				channels: state.channels.map( channel => {
					return channel.id === action.data ? { ...channel, authorized: true } : channel
				} ),
				publicChannels: state.publicChannels.map( channel => {
					return channel.id === action.data ? { ...channel, authorized: true } : channel
				} )
			});
		case 'application.heartbeat':
			return createState(state, { heartbeat: action.data });
		case 'application.dom.present':
			if (state.domPresent !== action.data) {
				return createState(state, { domPresent: action.data });
			}
			return state;
		default:
			return state
	}
}