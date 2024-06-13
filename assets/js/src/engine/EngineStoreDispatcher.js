import * as ApplicationActions from "actions/application";
import * as LogActions from "actions/log";
import * as MessagesActions from "actions/messages";
import * as UiActions from "actions/ui";

/**
 * Registers to all engine events and dispatches corresponding actions in the store.
 */
export default class EngineStoreDispatcher {

	/**
	 * @param {Engine} engine
	 * @param {Object} store
	 */
	constructor(engine, store) {
		this.engine = engine;
		this.store = store;

		this.subscribeToEngineEvents();
		this.subscribeToActions();
	}

	subscribeToEngineEvents() {
		const store = this.store;

		this.engine.subscribe('maintenance', 'event', function (eventName, data) {
			store.dispatch(ApplicationActions.updateData(eventName, data));
		});
		this.engine.subscribe('maintenance', 'event:checkSum', function (data) {
			this.engine.updateConfiguration({ checksum: data });
		}.bind(this));
		this.engine.subscribe('log', undefined, function (level, content, details) {
			store.dispatch(LogActions.log(level, content, details));
		});
		this.engine.subscribe('messages', undefined, function (messages) {
			store.dispatch(MessagesActions.receive(messages));

			let incomingCandidates = messages.filter(
				message => !store.getState().ui.openedChannels.includes(message.channel.id)
						&& !store.getState().ui.ignoredChannels.includes(message.channel.id)
						&& message.own === false && message.live === true && message.channel.type === 'direct'
			);
			if (incomingCandidates.length > 0) {
				store.dispatch(ApplicationActions.detectIncomingChats(incomingCandidates));
			}
		});
		this.engine.subscribe('heartBeat', undefined, function (data) {
			store.dispatch(ApplicationActions.heartBeat(data));
		});
	}

	subscribeToActions() {
		this.engine.subscribe('actions', undefined, function (actions) {
			actions.map( action => this.handleAction(action.command.name, action.command.data) );
		}.bind(this));
	}

	handleAction(name, data) {
		const state = this.store.getState();

		switch (name) {
			case 'deleteMessage':
				this.store.dispatch(MessagesActions.deleteMessage(data.id, data.channel.id));
				break;
			case 'deleteMessages':
				this.store.dispatch(MessagesActions.deleteMessages(data.ids));
				break;
			case 'refreshMessageReactionsCounters':
				this.store.dispatch(MessagesActions.refreshMessageReactionsCounters(data.id, data.reactions));
				break;
			case 'refreshMessage':
				if (state.messages.received[data.channel.id] && state.messages.received[data.channel.id].find( message => message.id === data.id )) {
					this.store.dispatch(MessagesActions.refreshMessage(data.id, data.channel.id));
				}
				break;
			case 'refreshMessageIfLocked':
				if (state.messages.received[data.channel.id] && state.messages.received[data.channel.id].find( message => message.id === data.id && message.locked )) {
					this.store.dispatch(MessagesActions.refreshMessage(data.id, data.channel.id));
				}
				break;
			case 'refreshUserName':
				this.store.dispatch(MessagesActions.refreshSender(data.id, data.name));
				break;
			case 'refreshChannelName':
				this.store.dispatch(ApplicationActions.refreshChannel(data.id, data.name));
				break;
			case 'incomingStream':
				this.store.dispatch(UiActions.requestStream(data));
				break;
		}
	}

}