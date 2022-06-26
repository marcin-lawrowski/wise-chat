/**
 * Abstract engine class.
 */
export default class Engine {

	/**
	 * @param {Object} configuration
	 */
	constructor(configuration) {
		this.configuration = configuration;
	}

	start() {
		console.log('Not implemented');
	}

	stop() {
		console.log('Not implemented');
	}

	/**
	 * Subscribes to the events:
	 * - maintenance
	 * - log (error, debug)
	 * - message
	 * - direct (sender)
	 * - pendingChatReceived
	 * - messageArrived
	 * - heartBeat
	 *
	 * @param {string} eventName
	 * @param {string=} eventSelector
	 * @param {function} listener
	 */
	subscribe(eventName, eventSelector, listener) {
		console.log('Not implemented');
	}

	/**
	 * @param {string} eventName
	 * @param {string=} eventSelector
	 * @param {function} listener
	 */
	unsubscribe(eventName, eventSelector, listener) {
		console.log('Not implemented');
	}

	/**
	 * Sends the message. All the listeners must be specified.
	 *
	 * @param {Object} message
	 * @param {Function} successListener
	 * @param {Function} progressListener
	 * @param {Function} errorListener
	 */
	sendMessage(message, successListener, progressListener, errorListener) {
		console.log('Not implemented');
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
		console.log('Not implemented');
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
		console.log('Not implemented');
	}

	/**
	 * Updates the configuration object.
	 *
	 * @param {Object} patch
	 */
	updateConfiguration(patch) {
		console.log('Not implemented');
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
		console.log('Not implemented');
	}

	/**
	 * Triggers maintenance actions.
	 */
	triggerMaintenance() {
		console.log('Not implemented');
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
		console.log('Not implemented');
	}

}