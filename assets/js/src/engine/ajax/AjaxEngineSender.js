import $ from "jquery";
import EventEmitter from "events";

export default class AjaxEngineSender  {

	/**
	 * @param {Object} configuration
	 */
	constructor(configuration) {
		this.emitter = new EventEmitter();
		this.configuration = configuration;
		this.messageEndpoint = configuration.engines.ajax.apiWPEndpointBase + '?action=wise_chat_message_endpoint';
		this.prepareImageEndpoint = configuration.engines.ajax.apiEndpointBase + '?action=wise_chat_prepare_image_endpoint';
	}

	/**
	 * @param {string} eventName Valid event names: log
	 * @param {function} listener
	 */
	subscribe(eventName, listener) {
		this.emitter.on(eventName, listener);
	}

	/**
	 * @param {string} eventName Valid event names: log
	 * @param {function} listener
	 */
	unsubscribe(eventName, listener) {
		this.emitter.removeListener(eventName, listener);
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
		if (!$.isFunction(successListener) || !$.isFunction(progressListener) || !$.isFunction(errorListener)) {
			throw new Error('Missing listeners');
		}
		let that = this;

		progressListener(0);

		let data = {};
		if ($.isPlainObject(message.customParameters)) {
			data = message.customParameters;
		}
		data['attachments'] = message.attachments;
		data['channelId'] = message.channelId;
		data['message'] = message.content;
		data['checksum'] = message.checksum;
		$.ajax({
				type: "POST",
				url: this.messageEndpoint,
				data: data,
				progressUpload: function(evt) {
					if (evt.lengthComputable) {
						let percent = parseInt(evt.loaded / evt.total * 100);
						if (percent > 100) {
							percent = 100;
						}

						progressListener(percent);
					}
				}
			})
			.done(function(result) {
				try {
					let response = result;
					if (response.error) {
						errorListener(response.error);
					} else {
						successListener(response);
					}
				} catch (e) {
					that.logDebug('[onMessageSent] [result]', result);
					that.logDebug('[onMessageSent] [exception]', e.toString());
					that.logError('Unknown error: ' + e.toString());
					errorListener('Unknown error: ' + e.toString());
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				if (typeof(jqXHR.status) !== 'undefined' && jqXHR.status === 0) {
					errorListener('No network connection');
					return;
				}

				try {
					let response = $.parseJSON(jqXHR.responseText);
					if (response.error) {
						errorListener(response.error);
					} else {
						errorListener('Unknown server error occurred: ' + errorThrown);
					}
				} catch (e) {
					that.logDebug('[onMessageSentError] [responseText]', jqXHR.responseText);
					that.logDebug('[onMessageSentError] [errorThrown]', errorThrown);
					that.logDebug('[onMessageSentError] [exception]', e.toString());
					that.logError('Server error: ' + errorThrown);
					errorListener('Server error: ' + errorThrown);
				}
			});
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
		if (!$.isFunction(successListener) || !$.isFunction(progressListener) || !$.isFunction(errorListener)) {
			throw new Error('Missing listeners');
		}
		let that = this;

		progressListener(0);

		$.ajax({
				type: "POST",
				url: this.prepareImageEndpoint,
				data: {
					data: imageSource,
					checksum: this.configuration.checksum
				},
				progressUpload: function(event) {
					if (event.lengthComputable) {
						let percent = parseInt(event.loaded / event.total * 100);
						if (percent > 100) {
							percent = 100;
						}
						progressListener(percent);
					}
				}
			})
			.done(function(result) {
				progressListener(100);
				successListener(result);
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				if (typeof(jqXHR.status) !== 'undefined' && jqXHR.status === 0) {
					errorListener('No network connection');
					return;
				}

				try {
					let response = $.parseJSON(jqXHR.responseText);
					if (response.error) {
						errorListener(response.error);
					} else {
						errorListener('Unknown server error occurred: ' + errorThrown);
					}
				} catch (e) {
					that.logDebug('[onImagePrepareError] [responseText]', jqXHR.responseText);
					that.logDebug('[onImagePrepareError] [errorThrown]', errorThrown);
					that.logDebug('[onImagePrepareError] [exception]', e.toString());
					that.logError('Server error: ' + errorThrown);
					errorListener('Server error: ' + errorThrown);
				}
			});
	}

	logError(message) {
		this.emitter.emit('log', 'error', message);
	}

	logDebug(message, additionalData = null) {
		this.emitter.emit('log', 'debug', '[engine.ajax.AjaxEngineSender] ' + message, additionalData);
	}

}