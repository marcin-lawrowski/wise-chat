import React from "react";
import { connect } from "react-redux";
import { clearNotifications } from "actions/ui";

class Notifications extends React.Component {

	constructor(props) {
		super(props);

		const sounds = [];
		this.soundRefs = {};
		for (const eventName in props.configuration.notifications) {
			if (!props.configuration.notifications.hasOwnProperty(eventName)) {
				continue;
			}
			if (this.props.configuration.notifications[eventName].sound) {
				sounds.push({ event: eventName, file: this.props.configuration.notifications[eventName].sound});
				this.soundRefs[eventName] = React.createRef();
			}
		}

		this.state = {
			sounds: sounds
		};

		this.rawTitle = document.title;
		this.isWindowFocused = true;
		this.isTitleNotificationVisible = false;
		this.notificationNumber = 0;

		this.handleBlur = this.handleBlur.bind(this);
		this.handleFocus = this.handleFocus.bind(this);
		this.showTitleNotificationAnimStep1 = this.showTitleNotificationAnimStep1.bind(this);
	}

	componentDidMount() {
		window.addEventListener('blur', this.handleBlur);
		window.addEventListener('focus', this.handleFocus);
	}

	componentWillUnmount() {
		window.removeEventListener('blur', this.handleBlur);
		window.removeEventListener('focus', this.handleFocus);
	}

	componentDidUpdate(prevProps) {
		const newNotifications = this.props.notifications !== prevProps.notifications && this.props.notifications.length > 0;

		if (newNotifications) {
			this.handleNotifications();
			this.props.clearNotifications();
		}
	}

	showTitleNotification() {
		if (!this.isTitleNotificationVisible) {
			this.isTitleNotificationVisible = true;
			this.rawTitle = document.title;
		}
		this.notificationNumber++;
		document.title = '(' + this.notificationNumber + ') (!) ' + this.rawTitle;
		setTimeout(this.showTitleNotificationAnimStep1, 1500);
	}

	showTitleNotificationAnimStep1() {
		if (this.isTitleNotificationVisible) {
			document.title = '(' + this.notificationNumber + ') ' + this.rawTitle;
		}
	}

	hideTitleNotification() {
		if (this.isTitleNotificationVisible) {
			document.title = this.rawTitle;
			this.isTitleNotificationVisible = false;
			this.notificationNumber = 0;
		}
	}

	handleBlur() {
		this.isWindowFocused = false;
	}

	handleFocus() {
		this.isWindowFocused = true;
		this.hideTitleNotification();
	}

	handleNotifications() {
		this.props.notifications.map( notification => {
			switch (notification.event) {
				case 'newMessage':
					this.notifyNewMessage()
					break;
				default:
					this.playSound(notification.event);
			}
		});
	}

	notifyNewMessage() {
		if (this.props.configuration.notifications.newMessage.title && !this.isWindowFocused) {
			this.showTitleNotification();
		}

		this.playSound('newMessage');
	}

	playSound(event) {
		if (!this.props.user.settings.muteSounds && this.soundRefs[event] && this.soundRefs[event].current.play) {
			this.soundRefs[event].current.play();
		}
	}

	render() {
		return(
			<React.Fragment>
				{ this.state.sounds.map( sound =>
					<audio key={ sound.event } ref={ this.soundRefs[sound.event] } preload="auto" data-event={ sound.event }>
						<source src={ this.props.configuration.baseDir + 'sounds/' + sound.file + '.wav' } type="audio/x-wav" />
						<source src={ this.props.configuration.baseDir + 'sounds/' + sound.file + '.mp3' } type="audio/ogg" />
						<source src={ this.props.configuration.baseDir + 'sounds/' + sound.file + '.ogg' } type="audio/mpeg" />
					</audio>
				)}
			</React.Fragment>
		);
	}

}

export default connect(
	(state) => ({
		configuration: state.configuration,
		user: state.application.user,
		notifications: state.ui.notifications
	}),
	{ clearNotifications }
)(Notifications);