import $ from "jquery";
import EventEmitter from "events";
import Engine from "../Engine";
import AjaxEngineMaintenance from "./AjaxEngineMaintenance";
import AjaxEngineSender from "./AjaxEngineSender";

export default class AjaxEngine extends Engine {

	/**
	 * @param {Object} configuration
	 */
	constructor(configuration) {
		super(configuration);

		this.emitter = new EventEmitter();
		this.maintenance = new AjaxEngineMaintenance(configuration);
		this.sender = new AjaxEngineSender(configuration);
		this.MESSAGES_REFRESH_TIMEOUT = configuration.engines.ajax.refresh;
		this.messagesEndpoint = configuration.engines.ajax.apiMessagesEndpointBase + '?action=wise_chat_messages_endpoint';
		this.pastMessagesEndpoint = configuration.engines.ajax.apiWPEndpointBase + '?action=wise_chat_past_messages_endpoint';
		this.userCommandEndpoint = configuration.engines.ajax.apiWPEndpointBase + '?action=wise_chat_user_command_endpoint';
		this.authEndpoint = configuration.engines.ajax.apiWPEndpointBase + '?action=wise_chat_auth_endpoint';
		this.idsCache = {};
		this.lastId = 0;
		this.lastCheckTime = null;
		this.isInitialized = false;
		this.currentRequest = null;
		this.messagesCallback = function() { };
		this.messagesErrorCallback = function() { };
		this.debugLoggerCallback = function() { };
		this.interval = null;
		this.initialDone = false;

		this.checkNewMessages = this.checkNewMessages.bind(this);
		this.onNewMessagesArrived = this.onNewMessagesArrived.bind(this);
		this.onMessageArrivalError = this.onMessageArrivalError.bind(this);
		this.onAuthenticationComplete = this.onAuthenticationComplete.bind(this);
	}

	start() {
		if (this.isInitialized === true) {
			return;
		}
		this.isInitialized = true;

		// wait for authentication:
		this.maintenance.subscribe('event:user', this.onAuthenticationComplete);

		// start the maintenance init task:
		this.maintenance.start();
	}

	stop() {
		this.maintenance.stop();
		if (this.interval) {
			clearInterval(this.interval);
			this.interval = null;
		}
		this.isInitialized = false;

		// stop listening for authentication:
		this.maintenance.unsubscribe('event:user', this.onAuthenticationComplete);
	}

	/**
	 * @param {string} eventName
	 * @param {string=} eventSelector
	 * @param {function} listener
	 */
	subscribe(eventName, eventSelector, listener) {
		switch (eventName) {
			case 'heartBeat':
				this.emitter.on('heartBeat', listener);
				break;
			case 'maintenance':
				this.maintenance.subscribe(eventSelector, listener);
				break;
			case 'log':
				this.maintenance.subscribe('log', listener);
				this.sender.subscribe('log', listener);
				this.emitter.on('log', listener);
				break;
			default:
				this.emitter.on(eventName, listener);
		}
	}

	/**
	 * @param {string} eventName
	 * @param {string=} eventSelector
	 * @param {function} listener
	 */
	unsubscribe(eventName, eventSelector, listener) {
		switch (eventName) {
			case 'heartBeat':
				this.emitter.removeListener('heartBeat', listener);
				break;
			case 'maintenance':
				this.maintenance.unsubscribe(eventSelector, listener);
				break;
			case 'log':
				this.maintenance.unsubscribe('log', listener);
				this.sender.unsubscribe('log', listener);
				this.emitter.removeListener('log', listener);
				break;
			default:
				this.emitter.removeListener(eventName, listener);
		}
	}

	onAuthenticationComplete(user) {
		if (!this.isInitialized) {
			return;
		}

		if (user) {
			// begin the loop only if user has been received:
			if (!this.interval) {
				this.checkNewMessages();
			}
		} else {
			// stop the loop:
			if (this.interval) {
				clearInterval(this.interval);
				this.interval = null;
			}
		}

		// TODO: slow down the maintenance task to save the resources
	}

	/**
	 * Checks for new messages. It does not make a new request if the previous one did not complete.
	 */
	checkNewMessages() {
		if (this.currentRequest !== null && this.currentRequest.readyState > 0 && this.currentRequest.readyState < 4) {
			return;
		}

		// do not request for messages if the engine is disabled:
		if (this.isInitialized === false) {
			return;
		}

		let data = {
			channelIds: this.configuration.channelIds,
			lastId: this.lastId,
			lastCheckTime: this.lastCheckTime,
			checksum: this.configuration.checksum,
			init: this.initialDone ? 0 : 1
		};
		if (this.configuration.isMultisite === true) {
			data['blogId'] = this.configuration.blogId;
		}

		this.currentRequest = $.ajax({
				type: "GET",
				url: this.messagesEndpoint,
				data: data
			})
			.done(this.onNewMessagesArrived)
			.fail(this.onMessageArrivalError);
	}

	/**
	 * Executed when AJAX request completes successfully.
	 * In case the method is called for the first time it starts the actual loop.
	 *
	 * @param {string} result
	 */
	onNewMessagesArrived(result) {
		// begin the actual loop:
		if (!this.interval) {
			this.initialDone = true;
			this.interval = setInterval(this.checkNewMessages, this.MESSAGES_REFRESH_TIMEOUT);
		}

		try {``
			let response = result;

			if (response.result) {
				let messagesFiltered = [];
				for (let x = 0; x < response.result.length; x++) {
					let msg = response.result[x];
					let messageId = msg['id'];
					if (!this.idsCache[messageId]) {
						this.lastId = messageId;
						messagesFiltered.push(msg);
						this.idsCache[messageId] = true;
					}
				}
				response.result = messagesFiltered;
				this.processMessages(response);
			}
		} catch (e) {
			this.logDebug('[onNewMessagesArrived] [result]', result);
			this.logDebug('[onNewMessagesArrived] [exception]', + e.toString());

			let errorDetails = '';
			if ($.type(result) === "string") {
				let split = result.split("\n");
				if (split.length > 1) {
					errorDetails = ", " + split[1];
				}
			} else {
				errorDetails = result;
			}
			this.logError('Server error: ' + e.toString() + errorDetails);
		}
	}

	/**
	 * Executed when AJAX request completes with an error.
	 *
	 * @param {Object} jqXHR
	 * @param {string} textStatus
	 * @param {Object} errorThrown
	 */
	onMessageArrivalError(jqXHR, textStatus, errorThrown) {
		// ignore network problems:
		if (typeof(jqXHR.status) != 'undefined' && jqXHR.status === 0) {
			return;
		}

		try {
			let response = $.parseJSON(jqXHR.responseText);
			if (response.error) {
				this.logError(response.error);
			}
		} catch (e) {
			this.logDebug('[onMessageArrivalError] [responseText]', jqXHR.responseText);
			this.logDebug('[onMessageArrivalError] [errorThrown]', errorThrown);
			this.logError('Server error: ' + errorThrown);
		}
	}

	processMessages(response) {
		this.emitter.emit('heartBeat', {
			nowTime: response.nowTime
		});

		// store the last check time to use in the Ultra engine:
		this.lastCheckTime = response.nowTime ? response.nowTime : this.lastCheckTime;

		if (response.result && response.result.length > 0) {
			if (this.configuration.rights.receiveMessages) {
				this.emitter.emit('messages', response.result);
			}
		}
	}

	/**
	 * Sends a message using AJAX call. All the listeners must be specified.
	 *
	 * @param {Object} message
	 * @param {Function} successListener
	 * @param {Function} progressListener
	 * @param {Function} errorListener
	 */
	sendMessage(message, successListener, progressListener, errorListener) {
		if (!this.isInitialized) {
			return;
		}

		let that = this;
		let extendedSuccessListener = (response) => {
			that.checkNewMessages();
			successListener(response);
		};
		this.sender.sendMessage(message, extendedSuccessListener, progressListener, errorListener);
	}

	/**
	 * Prepares the image. All the listeners must be specified.
	 *
	 * @param {string} imageSource
	 * @param {Function} successListener
	 * @param {Function} progressListener
	 * @param {Function} errorListener
	 */
	prepareImage(imageSource, successListener, progressListener, errorListener) {
		if (!this.isInitialized) {
			return;
		}
		this.sender.prepareImage(imageSource, successListener, progressListener, errorListener);
	}

	/**
	 * Sends user command.
	 *
	 * @param {String} command
	 * @param {Object} parameters
	 * @param {Function} successListener
	 * @param {Function} errorListener
	 */
	sendUserCommand(command, parameters, successListener, errorListener) {
		if (!$.isFunction(successListener) || !$.isFunction(errorListener)) {
			throw new Error('Missing listeners');
		}

		const data = {
			parameters: parameters,
			command: command,
			checksum: this.configuration.checksum
		};

		$.ajax({
				type: "POST",
				url: this.userCommandEndpoint,
				data: data
			})
			.done(function(result) {
				try {
					let response = result;
					if (response && response.error) {
						errorListener(response.error);
					} else {
						successListener(response);
					}
				} catch (e) {
					this.logDebug('[sendUserCommand] [result]', result);
					this.logDebug('[sendUserCommand] [exception]', e.toString());
					errorListener('Unknown error: ' + e.toString());
				}
			}.bind(this))
			.fail(this.commonFailFunction('sendUserCommand', errorListener));
	}

	/**
	 * Sends auth command.
	 *
	 * @param {String} mode
	 * @param {Object} parameters
	 * @param {Function} successListener
	 * @param {Function} errorListener
	 */
	auth(mode, parameters, successListener, errorListener) {
		if (!$.isFunction(successListener) || !$.isFunction(errorListener)) {
			throw new Error('Missing listeners');
		}

		let that = this;
		const data = {
			parameters: parameters,
			mode: mode,
			checksum: this.configuration.checksum
		};

		$.ajax({
				type: "POST",
				url: this.authEndpoint,
				data: data
			})
			.done(function(result) {
				try {
					let response = result;
					if (response && response.error) {
						errorListener(response.error);
					} else {
						successListener(response);
					}
				} catch (e) {
					that.logDebug('[auth] [result]', result);
					that.logDebug('[auth] [exception]', e.toString());
					errorListener('Unknown error: ' + e.toString());
				}
			})
			.fail(this.commonFailFunction('auth', errorListener));
	}

	/**
	 * Triggers maintenance actions.
	 */
	triggerMaintenance() {
		this.maintenance.performFullMaintenanceRequest();
	}

	/**
	 * Load past messages.
	 *
	 * @param {String} channelId
	 * @param {String} beforeMessage
	 * @param {Function} successListener
	 * @param {Function} errorListener
	 */
	loadPastMessages(channelId, beforeMessage, successListener, errorListener) {
		if (this.isInitialized === false) {
			return;
		}

		let that = this;
		let data = {
			channelId: channelId,
			beforeMessage: beforeMessage,
			checksum: this.configuration.checksum
		};
		if (this.configuration.isMultisite === true) {
			data['blogId'] = this.configuration.blogId;
		}

		$.ajax({
				type: "GET",
				url: this.pastMessagesEndpoint,
				data: data
			})
			.done(function(result) {
				try {
					let response = result;
					if (response && response.error) {
						errorListener(response.error);
					} else {
						successListener(response.result);
					}
				} catch (e) {
					that.logDebug('[loadPastMessages] [result]', result);
					that.logDebug('[loadPastMessages] [exception]', e.toString());
					errorListener('Unknown error: ' + e.toString());
				}
			})
			.fail(this.commonFailFunction('loadPastMessages', errorListener));
	}

	/**
	 * Updates the configuration object.
	 *
	 * @param {Object} patch
	 */
	updateConfiguration(patch) {
		this.maintenance.updateConfiguration(patch);
		this.configuration = Object.assign({}, this.configuration, patch);
	}

	logError(message) {
		this.emitter.emit('log', 'error', message);
	}

	logDebug(message, additionalData = null) {
		this.emitter.emit('log', 'debug', '[engine.ajax.AjaxEngine] ' + message, additionalData);
	}

	/**
	 * @param {String} method
	 * @param {Function} errorListener
	 * @returns {function(...[*]=)}
	 */
	commonFailFunction(method, errorListener) {
		return function(jqXHR, textStatus, errorThrown) {
			if (typeof(jqXHR.status) !== 'undefined' && jqXHR.status === 0) {
				errorListener('No network connection');
				return;
			}

			try {
				const response = $.parseJSON(jqXHR.responseText);
				errorListener(response && response.error ? response.error : 'Unknown server error occurred: ' + errorThrown);
			} catch (e) {
				this.logDebug(`[${method}] [responseText]`, jqXHR.responseText);
				this.logDebug(`[${method}] [errorThrown]`, errorThrown);
				this.logDebug(`[${method}] [exception]`, e.toString());
				errorListener('Server error: ' + errorThrown);
			}
		}.bind(this);
	}
}