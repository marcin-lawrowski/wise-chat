const defaultState = {
	domPresent: true,
	channels: [], // this is global channels storage (both those online and past / offline)
	publicChannels: [],
	directChannels: [],
	autoOpenChannel: undefined,
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
	recentChats: [], // recent chats loaded on init
	incomingChats: [], // incoming chats detected in runtime, mostly from yet unknown channels
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
		case 'channels':
			if (action.data.length === 0) {
				return state;
			}

			const channelCandidates = [
				...state.channels.filter( candidate => !action.data.find( channel => channel.id === candidate.id) ),
				...action.data
			];

			return createState(state, {
				channels: JSON.stringify(channelCandidates) !== JSON.stringify(state.channels) ? channelCandidates : state.channels
			});
		case 'publicChannels':
			// add to global channels storage only if not present:
			const publicChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );
			const channelCandidatesOfPublic = publicChannelCandidates.length > 0 ? [...state.channels, ...publicChannelCandidates] : state.channels;

			return createState(state, {
				publicChannels: JSON.stringify(state.publicChannels) !== JSON.stringify(action.data) ? action.data : state.publicChannels,
				channels: JSON.stringify(channelCandidatesOfPublic) !== JSON.stringify(state.channels) ? channelCandidatesOfPublic : state.channels
			});
		case 'directChannels':
			// add to global channels storage only if not present:
			const directChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );
			const channelCandidatesOfDirect = directChannelCandidates.length > 0 ? [...state.channels, ...directChannelCandidates] : state.channels;

			return createState(state, {
				directChannels: JSON.stringify(state.directChannels) !== JSON.stringify(action.data) ? action.data : state.directChannels,
				channels: JSON.stringify(channelCandidatesOfDirect) !== JSON.stringify(state.channels) ? channelCandidatesOfDirect : state.channels
			});
		case 'recentChats':
			const recentChatsChannels = action.data.map( recentChat => recentChat.channel );
			const channelsAltered = [
				// update existing:
				...state.channels.map( channel => recentChatsChannels.find( recentChatChannel => channel.id === recentChatChannel.id ) ? recentChatsChannels.find( recentChatChannel => channel.id === recentChatChannel.id ) : channel ),
				// add new:
				...recentChatsChannels.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) )
			];

			return createState(state, {
				recentChats: action.data,
				channels: JSON.stringify(channelsAltered) !== JSON.stringify(state.channels) ? channelsAltered : state.channels
			});
		case 'autoOpenChannel':
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
		case 'application.recent.read':
			return createState(state, { recentChats: state.recentChats.map( recentChat => recentChat.channel.id !== action.data ? recentChat : { ...recentChat, read: true }) });
		case 'application.incoming':
			// detect unique incoming chats:
			const incomingChats = action.data
				.filter( message => message.own === false && message.live === true && message.channel.type === 'direct' )
				.filter( message => !state.incomingChats.find( incomingChat => incomingChat.channel === message.channel.id) );

			// add to global channels storage only if not present:
			const incomingChannelsCandidates = incomingChats
				.filter( message => !state.channels.find( channel => message.channel.id === channel.id) )
				.map( message => message.channel );

			return createState(state, {
				channels: incomingChannelsCandidates.length > 0 ? [ ...state.channels, ...incomingChannelsCandidates ] : state.channels,
				incomingChats: incomingChats.length > 0 ? [...state.incomingChats, ...incomingChats.map( message => ({ channel: message.channel.id, channelName: message.channel.name }) ) ] : state.incomingChats
			});
		case 'application.incoming.delete':
			return createState(state, {
				incomingChats: state.incomingChats.filter( incomingChat => !action.data.includes(incomingChat.channel) )
			});
		case 'application.channel.replace':
			return createState(state, {
				channels: state.channels.map( channel => {
					return channel.id === action.id ? { ...channel, name: action.name } : channel
				} ),
				publicChannels: state.publicChannels.map( channel => {
					return channel.id === action.id ? { ...channel, name: action.name } : channel
				} ),
				directChannels: state.publicChannels.map( channel => {
					return channel.id === action.id ? { ...channel, name: action.name } : channel
				} )
			});
		case 'application.channel.add':
			return createState(state, {
				channels: state.channels.find( channel => channel.id === action.channel.id ) ? state.channels : [ ...state.channels, action.channel ],
				publicChannels: action.channel.type === 'public' && !state.publicChannels.find( channel => channel.id === action.channel.id )
					? [ ...state.publicChannels, action.channel ]
					: state.publicChannels,
				directChannels: action.channel.type === 'direct' && !state.directChannels.find( channel => channel.id === action.channel.id )
					? [ ...state.directChannels, action.channel ]
					: state.directChannels,
			});
		case 'application.clear':
			return createState(state, { ...defaultState, checkSum: state.checkSum, i18n: state.i18n, heartbeat: state.heartbeat });
		default:
			return state
	}
}