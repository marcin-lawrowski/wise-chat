import * as ApplicationActions from "actions/application";
import * as LogActions from "actions/log";
import * as MessagesActions from "actions/messages";

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
		this.subscribeToMaintenanceActions();
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
		});
		this.engine.subscribe('heartBeat', undefined, function (data) {
			store.dispatch(ApplicationActions.heartBeat(data));
		});
	}

	subscribeToMaintenanceActions() {
		this.engine.subscribe('maintenance', 'actions', function (actions) {
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
			case 'refreshUserName':
				this.store.dispatch(MessagesActions.refreshSender(data.id, data.name));
				break;
		}
	}

}