const defaultState = {
	channels: [], // this is global channels storage (both those online and past / offline)
	browserChannels: [],
	autoOpenChannels: [],
	onlineUsersCounter: 0,
	channelMap: undefined,
	users: [],
	usersCounter: [],
	absentUsers: [],
	newUsers: [],
	userFeed: [],
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
		case 'browserChannels':
			// add to global channels storage only if not present:
			const browserChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );
			const channelCandidatesOfBrowser = (browserChannelCandidates.length > 0 ? [...state.channels, ...browserChannelCandidates] : state.channels).map( channel => action.data.find( channelCandidate => channel.id === channelCandidate.id ) ?? channel );

			return createState(state, {
				browserChannels: JSON.stringify(state.browserChannels) !== JSON.stringify(action.data) ? action.data : state.browserChannels,
				onlineUsersCounter: action.data.filter( channel => channel.online === true && channel.type === 'direct' ).length,
				channels: JSON.stringify(channelCandidatesOfBrowser) !== JSON.stringify(state.channels) ? channelCandidatesOfBrowser : state.channels
			});
		case 'autoOpenChannels':
			const operatorsChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );
			const channelCandidatesOfOperators = (operatorsChannelCandidates.length > 0 ? [...state.channels, ...operatorsChannelCandidates] : state.channels).map( channel => action.data.find( channelCandidate => channel.id === channelCandidate.id ) ?? channel );

			return createState(state, {
				autoOpenChannels: JSON.stringify(state.autoOpenChannels) !== JSON.stringify(action.data) ? action.data : state.autoOpenChannels,
				channels: JSON.stringify(channelCandidatesOfOperators) !== JSON.stringify(state.channels) ? channelCandidatesOfOperators : state.channels
			});
		case 'openChannels':
			const openChannelCandidates = action.data
				.filter( channelCandidate => !state.channels.find( channel => channel.id === channelCandidate.id) );
			const channelCandidatesOfOpens = (openChannelCandidates.length > 0 ? [...state.channels, ...openChannelCandidates] : state.channels).map( channel => action.data.find( channelCandidate => channel.id === channelCandidate.id ) ?? channel );

			return createState(state, {
				openChannels: JSON.stringify(state.openChannels) !== JSON.stringify(action.data) ? action.data : state.openChannels,
				channels: JSON.stringify(channelCandidatesOfOpens) !== JSON.stringify(state.channels) ? channelCandidatesOfOpens : state.channels
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
		case 'userFeed':
		case 'application.userFeed.merge':
			const currentIDs = state.userFeed.map( entry => entry.id );
			const newInput = action.data.filter( entry => !currentIDs.includes(entry.id) );

			return newInput.length > 0 ? createState(state, { userFeed: [ ...state.userFeed, ...newInput ].sort( (a, b) => a.created.localeCompare(b.created) ).reverse() }) : state;
		case 'users':
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
				browserChannels: state.browserChannels.map( channel => {
					return channel.id === action.data ? { ...channel, authorized: true } : channel
				} )
			});
		case 'application.heartbeat':
			return createState(state, { heartbeat: action.data });
		case 'application.recent.read':
			return createState(state, { recentChats: state.recentChats.map( recentChat => recentChat.channel.id !== action.data ? recentChat : { ...recentChat, read: true }) });
		case 'application.feed.read':
			return createState(state, { userFeed: state.userFeed.map( userFeedEntry => userFeedEntry.id !== action.data ? userFeedEntry : { ...userFeedEntry, seen: true }) });
		case 'application.incoming.add':
			return createState(state, { incomingChats: [...state.incomingChats, action.data ] });
		case 'application.incoming.delete':
			return createState(state, {
				incomingChats: state.incomingChats.filter( incomingChat => !action.data.includes(incomingChat.channelId) )
			});
		case 'application.channel.replace':
			return createState(state, {
				channels: state.channels.map( channel => {
					return channel.id === action.channel.id ? { ...channel, ...action.channel } : channel
				} ),
				browserChannels: state.browserChannels.map( channel => {
					return channel.id === action.channel.id ? { ...channel, ...action.channel } : channel
				} )
			});
		case 'application.channel.add':
			return createState(state, {
				channels: state.channels.find( channel => channel.id === action.channel.id )
					? state.channels.map( channel => channel.id === action.channel.id ? action.channel : channel )
					: [ ...state.channels, action.channel ],
				browserChannels: action.storageOnly
					? state.browserChannels
					: (
						state.browserChannels.find( channel => channel.id === action.channel.id )
						? state.browserChannels.map( channel => channel.id === action.channel.id ? action.channel : channel )
						: [ ...state.browserChannels, action.channel ]
					)
			});
		case 'application.channel.remove':
			return createState(state, {
				channels: state.channels.filter( channel => channel.id !== action.channelId ),
				browserChannels: state.browserChannels.filter( channel => channel.id !== action.channelId )
			});
		case 'application.clear':
			return createState(state, { ...defaultState, checkSum: state.checkSum, i18n: state.i18n, heartbeat: state.heartbeat });
		default:
			return state
	}
}