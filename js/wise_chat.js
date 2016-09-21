/**
 * Wise Chat core controller.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 * @link http://kaine.pl/projects/wp-plugins/wise-chat
 */
function WiseChatController(options) {
	var progressBar = new WiseChatProgressBar(options);
	var notifier = new WiseChatNotifier(options);
	var messagesHistory = new WiseChatMessagesHistory();
	var imageViewer = new WiseChatImageViewer();
	var dateFormatter = new WiseChatDateFormatter();
	var messageAttachments = new WiseChatMessageAttachments(options, imageViewer, progressBar);
	var dateAndTimeRenderer = new WiseChatDateAndTimeRenderer(options, dateFormatter);
	var messages = new WiseChatMessages(options, messagesHistory, messageAttachments, dateAndTimeRenderer, notifier, progressBar);
	var settings = new WiseChatSettings(options, messages);
	var maintenanceExecutor = new WiseChatMaintenanceExecutor(options, messages, notifier);
    var emoticonsPanel = new WiseChatEmoticonsPanel(options, messages);
	
	messages.start();
	maintenanceExecutor.start();
};

/**
 * WiseChatDateFormatter class. Formats dates given in UTC timezone.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatDateFormatter() {
	
	function makeLeadZero(number) {
		return (number < 10 ? '0' : '') + number;
	}
	
	/**
	* Parses date given in ISO format.
	* 
	* @param {String} isoDate Date in ISO format
	* 
	* @return {Date} Parsed date
	*/
	function parseISODate(isoDate) {
		var s = isoDate.split(/\D/);
		
		return new Date(Date.UTC(s[0], --s[1]||'', s[2]||'', s[3]||'', s[4]||'', s[5]||'', s[6]||''))
	}
	
	/**
	* Determines whether two dates have equal day, month and year.
	* 
	* @param {Date} firstDate
	* @param {Date} secondDate
	* 
	* @return {Boolean}
	*/
	function isSameDate(firstDate, secondDate) {
		var dateFormatStr = 'Y-m-d';
		
		return formatDate(firstDate, dateFormatStr) == formatDate(secondDate, dateFormatStr);
	}
	
	/**
	* Returns formatted date.
	* 
	* @param {Date} date Date to format as a string
	* @param {String} format Desired date format
	* 
	* @return {String} Formatted date
	*/
	function formatDate(date, format) {
		format = format.replace(/Y/, date.getFullYear());
		format = format.replace(/m/, makeLeadZero(date.getMonth() + 1));
		format = format.replace(/d/, makeLeadZero(date.getDate()));
		format = format.replace(/H/, makeLeadZero(date.getHours()));
		format = format.replace(/i/, makeLeadZero(date.getMinutes()));
		
		return format;
	}
	
	/**
	* Returns localized time without seconds.
	* 
	* @param {Date} date Date to format as a string
	* 
	* @return {String} Localized time
	*/
	function getLocalizedTime(date) {
		if (typeof (date.toLocaleTimeString) != "undefined") {
			var timeLocale = date.toLocaleTimeString();
			if ((timeLocale.match(/:/g) || []).length == 2) {
				timeLocale = timeLocale.replace(/:\d\d$/, '');
				timeLocale = timeLocale.replace(/:\d\d /, ' ');
				timeLocale = timeLocale.replace(/[A-Z]{2,4}\-\d{1,2}/, '');
				timeLocale = timeLocale.replace(/[A-Z]{2,4}/, '');
			}
			
			return timeLocale;
		} else {
			return formatDate(date, 'H:i');
		}
	}
	
	/**
	* Returns localized date.
	* 
	* @param {Date} date Date to format as a string
	* 
	* @return {String} Localized date
	*/
	function getLocalizedDate(date) {
		if (typeof (date.toLocaleDateString) != "undefined") {
			return date.toLocaleDateString();
		} else {
			return formatDate(date, 'Y-m-d');
		}
	}
	
	// public API:
	this.formatDate = formatDate;
	this.parseISODate = parseISODate;
	this.isSameDate = isSameDate;
	this.getLocalizedTime = getLocalizedTime;
	this.getLocalizedDate = getLocalizedDate;
};

/**
 * WiseChatMessageAttachments class. Attachments management.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatMessageAttachments(options, imageViewer, progressBar) {
	var IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif'];
	var prepareImageEndpoint = options.apiEndpointBase + '?action=wise_chat_prepare_image_endpoint';
	var container = jQuery('#' + options.chatId);
	var messageAttachmentsPanel = container.find('.wcMessageAttachments');
	var imageUploadPreviewImage = container.find('.wcImageUploadPreview');
	var imageUploadFile = container.find('.wcImageUploadFile');
	var attachmentClearButton = container.find('.wcAttachmentClear');
	var fileUploadFile = container.find('.wcFileUploadFile');
	var fileUploadNamePreview = container.find('.wcFileUploadNamePreview');
	var attachments = [];
	
	function showErrorMessage(message) {
		alert(message);
	}
	
	function addAttachment(type, data, name) {
		attachments.push({ type: type, data: data, name: name });
	}
	
	function showImageAttachment() {
		if (attachments.length > 0 && attachments[0].type === 'image') {
			imageViewer.show(attachments[0].data);
		}
	}
	
	function onImageUploadFileChange() {
		var fileInput = imageUploadFile[0];
		if (typeof FileReader === 'undefined' || fileInput.files.length === 0) {
			showErrorMessage('Unsupported operation');
			return;
		}
		
		var fileDetails = fileInput.files[0];
		if (fileDetails.size && fileDetails.size > options.imagesSizeLimit) {
			showErrorMessage(options.messages.messageSizeLimitError);
			return;
		}
		
		if (IMAGE_TYPES.indexOf(getExtension(fileDetails)) > -1) {
			var fileReader = new FileReader();
			fileReader.onload = function(event) {
				clearAttachments();
				prepareImage(event.target.result, function(preparedImageData) {
					if (typeof preparedImageData !== 'undefined' && preparedImageData.length > 0) {
						addAttachment('image', preparedImageData);

						imageUploadPreviewImage.show();
						imageUploadPreviewImage.attr('src', preparedImageData);
						messageAttachmentsPanel.show();
						imageUploadFile.val('');
					} else {
						showErrorMessage('Cannot prepare image due to server error');
					}
				});
			};
			fileReader.readAsDataURL(fileDetails);
		} else {
			showErrorMessage(options.messages.messageUnsupportedTypeOfFile);
		}
	}
	
	function prepareImage(imageSource, successCallback) {
		var that = this;
		
		progressBar.setValue(0);
		progressBar.show();

		jQuery.ajax({
			type: "POST",
			url: prepareImageEndpoint,
			data: {
				data: imageSource,
				checksum: options.checksum
			},
			progressUpload: function(event) {
				if (event.lengthComputable) {
					var percent = parseInt(event.loaded / event.total * 100);
					if (percent > 100) {
						percent = 100;
					}
					progressBar.setValue(percent);
				}
			}
		})
		.done(function(result) {
			progressBar.hide();
			successCallback.apply(that, [result]);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			progressBar.hide();
			try {
				var response = jQuery.parseJSON(jqXHR.responseText);
				if (typeof response.error != 'undefined') {
					showErrorMessage(response.error);
				} else {
					showErrorMessage('Image preparation error occurred');
				}
			}
			catch (e) {
				showErrorMessage('Unknown image preparation error occurred');
			}
		});
	}
	
	function onFileUploadFileChange() {
		var fileInput = fileUploadFile[0];
		if (typeof FileReader === 'undefined' || fileInput.files.length === 0) {
			showErrorMessage('Unsupported operation');
			return;
		}
		
		var fileDetails = fileInput.files[0];
		if (options.attachmentsValidFileFormats.indexOf(getExtension(fileDetails)) > -1) {
			var fileReader = new FileReader();
			var fileName = fileDetails.name;
			
			if (fileDetails.size > options.attachmentsSizeLimit) {
				showErrorMessage(options.messages.messageSizeLimitError);
			} else {
				fileReader.onload = function(event) {
					clearAttachments();
					addAttachment('file', event.target.result, fileName);
					fileUploadNamePreview.html(fileName);
					fileUploadNamePreview.show();
					messageAttachmentsPanel.show();
				};
				fileReader.readAsDataURL(fileDetails);
			}
		} else {
			showErrorMessage(options.messages.messageUnsupportedTypeOfFile);
		}
	}
	
	function getExtension(fileDetails) {
		if (typeof fileDetails.name !== 'undefined') {
			var splitted = fileDetails.name.split('.');
			if (splitted.length > 1) {
				return splitted.pop().toLowerCase();
			}
		}
		
		return null;
	}
	
	function resetInput(inputField) {
		inputField.wrap('<form>').parent('form').trigger('reset');
		inputField.unwrap();
	}
	
	/**
	* Returns an array of prepared attachments.
	* 
	* @return {Array}
	*/
	function getAttachments() {
		return attachments;
	}
	
	/**
	* Clears all added attachments, resets and hides UI related to added attachments.
	*/
	function clearAttachments() {
		attachments = [];
		messageAttachmentsPanel.hide();
		fileUploadNamePreview.hide();
		fileUploadNamePreview.html('');
		imageUploadPreviewImage.hide();
		resetInput(fileUploadFile);
		resetInput(imageUploadFile);
	}
	
	// DOM events:
	imageUploadFile.change(onImageUploadFileChange);
	fileUploadFile.change(onFileUploadFileChange);
	attachmentClearButton.click(clearAttachments);
	imageUploadPreviewImage.click(showImageAttachment);
	
	// public API:
	this.getAttachments = getAttachments;
	this.clearAttachments = clearAttachments;
};

/**
 * WiseChatImageViewer
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatImageViewer() {
	var HOURGLASS_ICON = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wQEDB4ktAYXpwAAAb5JREFUSMe1lr9qFFEUh78rg8gWW1ikSLEWgkVq2SoYsbBIk1dYEAsxaJt3sLAIFkEEX0FSRlgMhKAPkEIQwZDChATSBLMQP5uz4bKZmZ3ZxR+cYs75nT9z7rlnJpFBfQC8B24xG/4Cz1NK38eKYoKwADxiPiwA1wnSpFUdAO+A+y0D/wBeppQ+5sqihHgAdIBRSumsSWT1bvgcNCF31Et1tWnp6mr4dCZtNw4zpXQB7AJrLdqzBuyGb6OKBuq52m3A7QZ3UGZPVW0CfgJvgc/As4r4H4CnwGvgXkrpDy36uh6VPVRPvYnTsJ2r662HWS3U/ZDH6kkW/CR0Y3sx041Re+qh+kXtq59C+qE7VHt1MWpXQkrpF7ACdIFhZhqGbiU4syX474gWHUU7FjP9YuiOprVo2iF/jUO8U3Hj94NTzJLgVYxgL0v4JqTI3rD9mEZ1v9WN7Hk7G9Pt8d5RN4LbaZPgelWE7JVctL3MXrkqqhLsqFvqbXVoNYbB2VJ32rTnMlbwptOxWbeuyxL0w/GJetUgwVVwVfuT8crGawm4AEbAi4ZdHYXPEvCtrvpl58dy3Rscx9dsnt+W41zxD60+eUN8VNiNAAAAAElFTkSuQmCC";
	
	var container = jQuery('body');
	var imagePreviewFade = container.find('.wcImagePreviewFade');
	var imagePreview = container.find('.wcImagePreview');
	if (imagePreviewFade.length === 0) {
		container.append('<div class="wcImagePreview"> </div><div class="wcImagePreviewFade"> </div>');
		imagePreviewFade = container.find('.wcImagePreviewFade');
		imagePreview = container.find('.wcImagePreview');
	}
	
	function show(imageSource) {
		clearRemnants();
		
		imagePreviewFade.show();
		addAndShowHourGlass();
		
		var imageElement = jQuery('<img style="display:none;" />');
		imageElement.on('load', function() {
			removeHourGlass();
			
			var image = jQuery(this);
			var additionalMargin = 20;
			var windowWidth = jQuery(window).width();
			var windowHeight = jQuery(window).height();
			image.show();
			
			if (image.width() > windowWidth && image.height() > windowHeight) {
				if (image.width() > image.height()) {
					image.width(windowWidth - additionalMargin);
				} else {
					image.height(windowHeight - additionalMargin);
				}
			} else if (image.width() > windowWidth) {
				image.width(windowWidth - additionalMargin);
			} else if (image.height() > windowHeight) {
				image.height(windowHeight - additionalMargin);
			}
			
			var topPosition = Math.max(0, ((windowHeight - jQuery(this).outerHeight()) / 2) + jQuery(window).scrollTop());
			var leftMargin = -1 * (image.width() / 2);
			imagePreview.css({
				top: topPosition + "px",
				marginLeft: leftMargin + "px"
			});
		});
		imageElement.attr('src', imageSource);
		imageElement.appendTo(imagePreview);
		imageElement.click(hide);
	}
	
	function hide() {
		clearRemnants();
		imagePreview.hide();
		imagePreviewFade.hide();
	}
	
	function clearRemnants() {
		imagePreview.find('img').remove();
	}
	
	function addAndShowHourGlass() {
		var windowHeight = jQuery(window).height();
		var imageElement = jQuery('<img class="wcHourGlass" />');
		var topPosition = Math.max(0, ((windowHeight - 24) / 2) + jQuery(window).scrollTop());
		
		imageElement.attr('src', HOURGLASS_ICON);
		imageElement.appendTo(imagePreview);
		imagePreview.css({
			top: topPosition + "px",
			marginLeft: "-12px"
		});
		imagePreview.show();
	}
	
	function removeHourGlass() {
		container.find('.wcHourGlass').remove();
	}
	
	// DOM events:
	imagePreviewFade.click(hide);
	
	// public API:
	this.show = show;
	this.hide = hide;
};

/**
 * WiseChatNotifier - window title and sound notifiers.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatNotifier(options) {
	var isWindowFocused = true;
	var isTitleNotificationVisible = false;
	var rawTitle = document.title;
	var notificationNumber = 0;
	var newMessageSoundNotification = null;
	var userLeftSoundNotification = null;
	var userJoinedSoundNotification = null;
	var userMentioningSoundNotification = null;

	function initializeSoundFeatures(soundFile, eventID) {
		if (soundFile != null && soundFile.length > 0) {
			var elementId = 'wcMessagesNotificationAudio' + eventID;
			var soundNotificationElement = jQuery('#' + elementId);
			if (soundNotificationElement.length > 0) {
				return soundNotificationElement;
			}
			
			var soundFileURLWav = options.baseDir + 'sounds/' + soundFile + '.wav';
			var soundFileURLMp3 = options.baseDir + 'sounds/' + soundFile + '.mp3';
			var soundFileURLOgg = options.baseDir + 'sounds/' + soundFile + '.ogg';
			var container = jQuery('body');
			
			container.append(
				'<audio id="' + elementId + '" preload="auto" >' +
					'<source src="' + soundFileURLWav + '" type="audio/x-wav" />' +
					'<source src="' + soundFileURLOgg + '" type="audio/ogg" />' +
					'<source src="' + soundFileURLMp3 + '" type="audio/mpeg" />' +
				'</audio>'
			);

			return jQuery('#' + elementId);
		}

		return null;
	}
	
	function showTitleNotification() {
		if (!isTitleNotificationVisible) {
			isTitleNotificationVisible = true;
			rawTitle = document.title;
		}
		notificationNumber++;
		document.title = '(' + notificationNumber + ') (!) ' + rawTitle;
		setTimeout(function() { showTitleNotificationAnimStep1(); }, 1500);
	}
	
	function showTitleNotificationAnimStep1() {
		if (isTitleNotificationVisible) {
			document.title = '(' + notificationNumber + ') ' + rawTitle;
		}
	}
	
	function hideTitleNotification() {
		if (isTitleNotificationVisible) {
			document.title = rawTitle;
			isTitleNotificationVisible = false;
			notificationNumber = 0;
		}
	}
	
	function onWindowBlur() {
		isWindowFocused = false;
	}
	
	function onWindowFocus() {
		isWindowFocused = true;
		hideTitleNotification();
	}
	
	function sendNotifications() {
		if (options.enableTitleNotifications && !isWindowFocused) {
			showTitleNotification();
		}
		if (!options.userSettings.muteSounds) {
			if (newMessageSoundNotification !== null && newMessageSoundNotification[0].play) {
				newMessageSoundNotification[0].play();
			}
		}
	}

	function sendNotificationForEvent(eventName) {
		if (!options.userSettings.muteSounds) {
			if (eventName == 'userLeft') {
				if (userLeftSoundNotification !== null && userLeftSoundNotification[0].play) {
					userLeftSoundNotification[0].play();
				}
			} else if (eventName == 'userJoined') {
				if (userJoinedSoundNotification !== null && userJoinedSoundNotification[0].play) {
					userJoinedSoundNotification[0].play();
				}
			} else if (eventName == 'userMentioning') {
				if (userMentioningSoundNotification !== null && userMentioningSoundNotification[0].play) {
					userMentioningSoundNotification[0].play();
				}
			}
		}
	}
	
	// start-up actions:
	newMessageSoundNotification = initializeSoundFeatures(options.soundNotification, 'NewMessage');
	userLeftSoundNotification = initializeSoundFeatures(options.leaveSoundNotification, 'UserLeft');
	userJoinedSoundNotification = initializeSoundFeatures(options.joinSoundNotification, 'UserJoined');
	userMentioningSoundNotification = initializeSoundFeatures(options.mentioningSoundNotification, 'UserMentioned');

	// DOM events:
	jQuery(window).blur(onWindowBlur);
	jQuery(window).focus(onWindowFocus);
	
	// public API:
	this.sendNotifications = sendNotifications;
	this.sendNotificationForEvent = sendNotificationForEvent;
}

/**
 * WiseChatDateAndTimeRenderer - renders date and time next to each message according to the settings.
 *
 * @author Marcin Ławrowski <marcin@kaine.pl>
 */
function WiseChatDateAndTimeRenderer(options, dateFormatter) {
	var spanDate = '<span class="wcMessageTimeDate">';
	var spanTime = '<span class="wcMessageTimeHour">';
	var spanClose = '</span>';
	var dateAndTimeMode = options.messagesTimeMode;
	
	function formatFullDateAndTime(date, nowDate, element) {
		if (dateFormatter.isSameDate(nowDate, date)) {
			element.html(spanTime + dateFormatter.getLocalizedTime(date) + spanClose);
		} else {
			element.html(
				spanDate + dateFormatter.getLocalizedDate(date) + spanClose + ' ' +
				spanTime + dateFormatter.getLocalizedTime(date) + spanClose
			);
		}
		element.attr('data-fixed', '1');
	}
	
	function formatElapsedDateAndTime(date, nowDate, element) {
		var yesterdayDate = new Date();
		var diffSeconds = parseInt((nowDate.getTime() - date.getTime()) / 1000);
		yesterdayDate.setDate(nowDate.getDate() - 1);
		
		var formattedDateAndTime = '';
		var isFixed = false;

		if (diffSeconds < 60) {
			if (diffSeconds <= 0) {
				diffSeconds = 1;
			}
			formattedDateAndTime = spanTime + diffSeconds + ' ' + options.messages.messageSecAgo + spanClose;
		} else if (diffSeconds < 60 * 60) {
			formattedDateAndTime = spanTime + parseInt(diffSeconds / 60) + ' ' + options.messages.messageMinAgo + spanClose;
		} else if (dateFormatter.isSameDate(nowDate, date)) {
			formattedDateAndTime = spanTime + dateFormatter.getLocalizedTime(date) + spanClose;
			isFixed = true;
		} else if (dateFormatter.isSameDate(yesterdayDate, date)) {
			formattedDateAndTime = spanDate + options.messages.messageYesterday + spanClose + ' ' + spanTime + dateFormatter.getLocalizedTime(date) + spanClose;
			isFixed = true;
		} else {
			formattedDateAndTime = spanDate + dateFormatter.getLocalizedDate(date) + spanClose + ' ' + spanTime + dateFormatter.getLocalizedTime(date) + spanClose;
			isFixed = true;
		}
		
		element.html(formattedDateAndTime);
		if (isFixed) {
			element.attr('data-fixed', '1');
		}
	}
	
	/**
	* Format all elements containing dates withing parent container.
	* 
	* @param {jQuery} date Date to format as a string
	* @param {String} nowISODate Now date
	* 
	*/
	function convertUTCMessagesTime(parentContainer, nowISODate) {
		if (dateAndTimeMode === 'hidden') {
			return;
		}
		parentContainer.find('.wcMessageTime:not([data-fixed])').each(function(index, element) {
			element = jQuery(element);
			
			var date = dateFormatter.parseISODate(element.data('utc'));
			var nowDate = dateFormatter.parseISODate(nowISODate);
			if (dateAndTimeMode === 'elapsed') {
				formatElapsedDateAndTime(date, nowDate, element);
			} else {
				formatFullDateAndTime(date, nowDate, element);
			}
		});
	}
	
	// public API:
	this.convertUTCMessagesTime = convertUTCMessagesTime;
}

/**
 * WiseChatProgressBar - controls the main progress bar.
 */
function WiseChatProgressBar(options) {
	var container = jQuery('#' + options.chatId);
	var progressBar = container.find('.wcMainProgressBar');
	
	// provide access to progress events to AJAX requests:
	(function addXhrProgressEvent(jQuery) {
		var originalXhr = jQuery.ajaxSettings.xhr;
		jQuery.ajaxSetup({
			xhr: function() {
				var req = originalXhr.call(jQuery.ajaxSettings), that = this;
				if (req) {
					if (typeof req.addEventListener == "function" && that.progress !== undefined) {
						req.addEventListener("progress", function(evt) {
							that.progress(evt);
						}, false);
					}
					if (typeof req.upload == "object" && that.progressUpload !== undefined) {
						req.upload.addEventListener("progress", function(evt) {
							that.progressUpload(evt);
						}, false);
					}
				}
				return req;
			}
		});
	})(jQuery);
	
	function show() {
		progressBar.show();
	}
	
	function hide() {
		progressBar.hide();
	}
	
	function setValue(value) {
		progressBar.attr("value", value);
	}
	
	// public API:
	this.show = show;
	this.hide = hide;
	this.setValue = setValue;
}

/**
 * WiseChatEmoticonsPanel class is responsible for displaying emoticons layer and inserting selected emoticon
 * into message input field.
 *
 * @param {Object} options
 * @param {WiseChatMessages} messages
 * @constructor
 */
function WiseChatEmoticonsPanel(options, messages) {
    var EMOTICONS_1 = [
        'zip-it', 'blush', 'angry', 'not-one-care', 'laugh-big', 'please', 'cool', 'minishock',
        'devil', 'silly', 'smile', 'devil-laugh', 'heart', 'not-guilty', 'hay',
        'in-love', 'meow', 'tease', 'gift', 'kissy', 'sad', 'speechless', 'goatse',
        'fools', 'why-thank-you', 'wink', 'angel', 'annoyed', 'flower', 'surprised',
        'female', 'laugh', 'ill', 'total-shock', 'zzz', 'clock', 'oh', 'mail', 'crazy',
        'cry', 'boring', 'geek'
    ];
    var EMOTICONS_SHORTCUTS_1 = {
        'smile': ':)', 'wink': ';)', 'laugh': ':D', 'laugh-big': 'xD',
        'sad': ':(', 'cry': ';(', 'kissy': ':*', 'silly': ':P',
        'crazy': ';P', 'angry': ':[', 'devil-laugh': ':>', 'devil': ':]', 'goatse': ':|'
    };
	var FILES_EXTENSION_1 = 'png';
	var SUBDIRECTORY_1 = '';
	var LAYER_WIDTH_1 = null;

	var EMOTICONS_2 = [
		'angry', 'bulb', 'cafe', 'clap', 'clouds', 'cry', 'devil', 'gift', 'handshake',
		'heart', 'kissy', 'laugh-big', 'no', 'ok', 'feel_peace', 'oh_please', 'rain', 'scared',
		'silly', 'snail', 'sun', 'baloons', 'bye', 'cake', 'cleaver', 'cool', 'cry_big',
		'drink', 'hat', 'heart_big', 'laugh', 'moon', 'offended', 'omg', 'a_phone',
		'question', 'sad', 'shy', 'smile', 'stars', 'wine'
	];
	var EMOTICONS_SHORTCUTS_2 = {
		'smile': ':)', 'wink': ';)', 'laugh': ':D', 'laugh-big': 'xD',
		'sad': ':(', 'cry': ';(', 'kissy': ':*', 'silly': ':P',
		'crazy': ';P', 'angry': ':[', 'devil-laugh': ':>', 'devil': ':]'
	};
	var FILES_EXTENSION_2 = 'gif';
	var SUBDIRECTORY_2 = 'set_2/';
	var LAYER_WIDTH_2 = 235;

	var EMOTICONS_3 = [
		'angel', 'confused', 'cthulhu', 'drugged', 'grinning', 'horrified', 'kawaii', 'madness',
		'shy', 'spiteful', 'terrified', 'tongue_out', 'tongue_out_up_left', 'winking_grinning',
		'angry', 'cool', 'cute', 'frowning', 'happy', 'hug', 'kissing', 'malicious', 'sick',
		'stupid', 'thumbs_down', 'tongue_out_laughing', 'unsure', 'winking_tongue_out', 'aww',
		'creepy', 'cute_winking', 'gasping', 'happy_smiling', 'irritated', 'laughing', 'naww',
		'smiling', 'surprised', 'thumbs_up', 'tongue_out_left', 'unsure_2', 'blushing', 'crying',
		'devil', 'greedy', 'heart', 'irritated_2', 'lips_sealed', 'i_am_pouting', 'speechless',
		'surprised_2', 'tired', 'tongue_out_up', 'winking'
	];
	var EMOTICONS_SHORTCUTS_3 = {
		'smiling': ':)', 'winking': ';)', 'laughing': ':D', 'madness': 'xD',
		'frowning': ':(', 'crying': ';(', 'kissing': ':*', 'tongue_out': ':P',
		'winking_tongue_out': ';P', 'angry': ':[', 'devil': ':>', 'devil': ':]', 'irritated': ':|'
	};
	var FILES_EXTENSION_3 = 'png';
	var SUBDIRECTORY_3 = 'set_3/';
	var LAYER_WIDTH_3 = 195;

	var EMOTICONS_4 = [
		'angel', 'beer', 'clock', 'crying', 'drink', 'eyeroll', 'glasses-cool', 'jump',
		'mad-tongue', 'sad', 'sick', 'smile-big', 'thinking', 'wilt',
		'angry', 'bomb', 'cloudy', 'cute', 'drool', 'fingers-crossed', 'go-away',
		'kiss', 'mail', 'shock', 'silly', 'smirk', 'tongue', 'wink',
		'arrogant', 'bye', 'coffee', 'devil', 'embarrassed', 'freaked-out', 'good',
		'laugh', 'mean', 'shout', 'sleepy', 'star', 'vampire', 'worship',
		'bad', 'cake', 'confused', 'disapointed', 'excruciating', 'giggle', 'in-love',
		'love', 'neutral', 'rotfl', 'shut-mouth', 'smile', 'struggle', 'weep', 'yawn',
		'beauty', 'hypnotized', 'island', 'quiet', 'rose', 'soccerball'
	];
	var EMOTICONS_SHORTCUTS_4 = {
		'smile': ':)', 'wink': ';)', 'laugh': ':D', 'smile-big': 'xD',
		'sad': ':(', 'crying': ';(', 'kiss': ':*', 'tongue': ':P',
		'silly': ';P', 'angry': ':[', 'devil': ':>', 'devil': ':]', 'neutral': ':|'
	};
	var FILES_EXTENSION_4 = 'png';
	var SUBDIRECTORY_4 = 'set_4/';
	var LAYER_WIDTH_4 = 280;

    var LAYER_ID = 'wcEmoticonsLayer' + options.chatId;
    var container = jQuery('#' + options.chatId);
    var insertEmoticonButton = container.find('.wcInsertEmoticonButton');
    var layer = jQuery('#' + LAYER_ID);

	var EMOTICONS = EMOTICONS_1;
	var EMOTICONS_SHORTCUTS = EMOTICONS_SHORTCUTS_1;
	var FILES_EXTENSION = FILES_EXTENSION_1;
	var SUBDIRECTORY = SUBDIRECTORY_1;
	var LAYER_WIDTH = null;
	switch (options.emoticonsSet) {
		case 2:
			EMOTICONS = EMOTICONS_2;
			EMOTICONS_SHORTCUTS = EMOTICONS_SHORTCUTS_2;
			FILES_EXTENSION = FILES_EXTENSION_2;
			SUBDIRECTORY = SUBDIRECTORY_2;
			LAYER_WIDTH = LAYER_WIDTH_2;
			break;
		case 3:
			EMOTICONS = EMOTICONS_3;
			EMOTICONS_SHORTCUTS = EMOTICONS_SHORTCUTS_3;
			FILES_EXTENSION = FILES_EXTENSION_3;
			SUBDIRECTORY = SUBDIRECTORY_3;
			LAYER_WIDTH = LAYER_WIDTH_3;
			break;
		case 4:
			EMOTICONS = EMOTICONS_4;
			EMOTICONS_SHORTCUTS = EMOTICONS_SHORTCUTS_4;
			FILES_EXTENSION = FILES_EXTENSION_4;
			SUBDIRECTORY = SUBDIRECTORY_4;
			LAYER_WIDTH = LAYER_WIDTH_4;
			break;
	}

    if (insertEmoticonButton.length > 0 && layer.length === 0) {
        layer = jQuery('<div />')
            .attr('id', LAYER_ID)
            .attr('class', 'wcEmoticonsLayer')
            .hide();
        jQuery('body').append(layer);

        // build buttons:
        for (var i = 0; i < EMOTICONS.length; i++) {
            var emoticon = EMOTICONS[i];
            var imageSrc = options.emoticonsBaseURL + SUBDIRECTORY + emoticon + '.' + FILES_EXTENSION;
            var button = jQuery('<a />')
                .attr('href', 'javascript://')
                .attr('title', emoticon)
                .append(jQuery('<img />').attr('src', imageSrc))
                .click(function (emoticon) {
                    return function () {
                        onEmoticonClick(emoticon);
                    }
                }(emoticon));
            layer.append(button);
        }
	}

	if (LAYER_WIDTH !== null) {
		layer.css('width', LAYER_WIDTH + 'px');
	}

    function hideLayer() {
        layer.hide();
        jQuery(document).unbind("mousedown", onDocumentMouseDown);
    }

    function showLayer() {
        layer.show();
        jQuery(document).bind("mousedown", onDocumentMouseDown);
    }

    function onDocumentMouseDown(event) {
        if (insertEmoticonButton[0] != event.target && layer[0] != event.target && !jQuery.contains(layer[0], event.target)) {
            hideLayer();
        }
    }

	function onInsertEmoticonButtonClick() {
        if (!layer.is(':visible')) {
            layer.css({
                top: (insertEmoticonButton.offset().top + insertEmoticonButton.outerHeight()) - layer.outerHeight(),
                left: insertEmoticonButton.offset().left - layer.outerWidth() - 5
            });
            showLayer();
        } else {
            hideLayer();
        }
	}

    var onEmoticonClick = function(emoticon) {
        var emoticonCode = EMOTICONS_SHORTCUTS[emoticon] ? EMOTICONS_SHORTCUTS[emoticon] : '<' + emoticon + '>';
        messages.appendTextToInputField(emoticonCode);
        hideLayer();
    };

	insertEmoticonButton.click(onInsertEmoticonButtonClick);
}