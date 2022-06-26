import $ from "jquery";
import EventEmitter from "events";

export default class AjaxEngineMaintenance  {

	/**
	 * @param {Object} configuration
	 */
	constructor(configuration) {
		this.emitter = new EventEmitter();
		this.configuration = configuration;
		this.started = false;
		this.REFRESH_TIMEOUT = 20000;
		this.ENDPOINT_URL = configuration.engines.ajax.apiWPEndpointBase + '?action=wise_chat_maintenance_endpoint';
		this.lastActionId = 0;
		this.full = true; // run full data request on page load
		this.request = null;
		this.interval = null;
		this.actionsIdsCache = {};

		this.performMaintenanceRequest = this.performMaintenanceRequest.bind(this);
		this.analyzeResponse = this.analyzeResponse.bind(this);
		this.onMaintenanceRequestError = this.onMaintenanceRequestError.bind(this);
	}

	/**
	 * Updates the configuration object.
	 *
	 * @param {Object} patch
	 */
	updateConfiguration(patch) {
		this.configuration = Object.assign({}, this.configuration, patch);
	}

	/**
	 * Starts the maintenance.
	 */
	start() {
		if (this.started === true) {
			return;
		}
		this.started = true;
		this.performMaintenanceRequest();
		this.interval = setInterval(this.performMaintenanceRequest, this.REFRESH_TIMEOUT);
	}

	/**
	 * Stops the maintenance.
	 */
	stop() {
		if (this.interval) {
			clearInterval(this.interval);
			this.interval = null;
		}
		this.started = false;
	}

	/**
	 * @param {string} eventName Valid event names: action, event, log
	 * @param {function} listener
	 */
	subscribe(eventName, listener) {
		this.emitter.on(eventName, listener);
	}

	/**
	 * @param {string} eventName Valid event names: action, event, log
	 * @param {function} listener
	 */
	unsubscribe(eventName, listener) {
		this.emitter.removeListener(eventName, listener);
	}

	/**
	 * @returns {boolean}
	 */
	isRequestStillRunning() {
		return this.request !== null && this.request.readyState > 0 && this.request.readyState < 4;
	}

	performFullMaintenanceRequest() {
		this.full = true;
		this.performMaintenanceRequest();
	}

	performMaintenanceRequest() {
		if (this.isRequestStillRunning()) {
			return;
		}

		let data = {
			full: this.full,
			fromActionId: this.lastActionId,
			channelIds: this.configuration.channelIds,
			checksum: this.configuration.checksum
		};

		this.request = $.ajax({
				url: this.ENDPOINT_URL,
				data: data
			})
			.done(this.analyzeResponse)
			.fail(this.onMaintenanceRequestError);
	}

	analyzeResponse(maintenance) {
		try {
			if (maintenance.lastActionId) {
				if (this.lastActionId < maintenance.lastActionId) {
					this.lastActionId = maintenance.lastActionId;
				}
			}
			if (maintenance.actions) {
				this.executeActions(maintenance.actions);
			}
			if (maintenance.events) {
				this.handleEvents(maintenance.events);
			}
			if (maintenance.error) {
				this.logError('Maintenance error occurred: ' + maintenance.error);
			} else {
				// mark init request completed when no errors were detected:
				this.full = false;
			}
		} catch (e) {
			this.logDebug('[analyzeResponse] [data]', maintenance);
			this.logDebug('[analyzeResponse] [exception]', e.message);
			this.logError('Maintenance error: corrupted data');
		}
	}

	executeActions(actions) {
		let actionsFinal = [];

		for (let x = 0; x < actions.length; x++) {
			let action = actions[x];
			let actionId = action.id;

			if (!this.actionsIdsCache[actionId]) {
				this.actionsIdsCache[actionId] = true;
				actionsFinal.push(action);
			}
		}

		this.emitter.emit('actions', actionsFinal);
	}

	handleEvents(events) {
		for (let x = 0; x < events.length; x++) {
			let event = events[x];
			this.emitter.emit('event', event.name, event.data);
			this.emitter.emit('event:' + event.name, event.data);
		}
	}

	onMaintenanceRequestError(jqXHR, textStatus, errorThrown) {
		// network problems ignore:
		if (typeof(jqXHR.status) != 'undefined' && jqXHR.status === 0) {
			return;
		}

		try {
			let response = $.parseJSON(jqXHR.responseText);
			if (response.error) {
				this.logError('Maintenance error: ' + response.error);
			} else {
				this.logError('Unknown maintenance error: ' + errorThrown);
			}
		} catch (e) {
			this.logDebug('[onMaintenanceRequestError] [responseText]', jqXHR.responseText);
			this.logDebug('[onMaintenanceRequestError] [errorThrown]', errorThrown);
			this.logDebug('[onMaintenanceRequestError] [exception]', e.message);
			this.logError('Maintenance fatal error: ' + errorThrown);
		}
	}

	logError(message) {
		this.emitter.emit('log', 'error', message);
	}

	logDebug(message, additionalData = null) {
		this.emitter.emit('log', 'debug', '[engine.ajax.AjaxEngineMaintenance] ' + message, additionalData);
	}
	
}