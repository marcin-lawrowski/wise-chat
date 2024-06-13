import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { focusChannel, openChannel, ignoreChannel, unreadAdd, confirm, notify } from "actions/ui";
import { deleteIncomingChats } from "actions/application";

class Incoming extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			isConfirmInProgress: false
		};

		this.incomingConfirmed = this.incomingConfirmed.bind(this);
		this.incomingCancelled = this.incomingCancelled.bind(this);
		this.incomingIgnored = this.incomingIgnored.bind(this);
	}

	componentDidUpdate(prevProps) {
		const incomingChatsChanged = this.props.incomingChats !== prevProps.incomingChats && this.props.incomingChats.length > 0;

		if (incomingChatsChanged) {
			if (this.props.configuration.interface.incoming.confirm) {
				const incoming = this.props.incomingChats[0];

				// ask for confirmation of the first incoming chat:
				if (this.state.isConfirmInProgress === false && !this.props.openedChannels.includes(incoming.channel)) {
					this.setState({isConfirmInProgress: true});
					this.props.confirm(incoming.channelName + ' ' + this.props.i18n.incomingAskApproval, this.incomingConfirmed, this.incomingCancelled, [{
						text: this.props.i18n.ignoreUser,
						callback: this.incomingIgnored
					}]);
					this.props.notify('newChat');
				}
			} else {
				// auto-open all incoming chats:
				this.props.incomingChats
					.map( incomingChat => {
						const channel = incomingChat.channel;

						if (!this.props.openedChannels.includes(channel)) {
							this.props.openChannel(channel);
							if (this.props.configuration.interface.incoming.focus) {
								this.props.focusChannel(channel);
							} else {
								this.props.unreadAdd(channel, 1);
							}
							this.props.notify('newChat');
						}
					});

				// mark the incoming chat handled:
				this.props.deleteIncomingChats(this.props.incomingChats.map( incomingChat => incomingChat.channel ));
			}
		}
	}

	incomingIgnored() {
		this.props.ignoreChannel(this.props.incomingChats[0].channel);
		this.deleteLastIncomingChat();
		this.setState({isConfirmInProgress: false});
	}

	incomingConfirmed() {
		const channel = this.props.incomingChats[0].channel;
		if (!this.props.openedChannels.includes(channel)) {
			this.props.openChannel(channel);
		}

		if (this.props.configuration.interface.incoming.focus) {
			this.props.focusChannel(channel);
		} else {
			this.props.unreadAdd(channel, 1);
		}

		this.deleteLastIncomingChat();
		this.setState({isConfirmInProgress: false});
	}

	incomingCancelled() {
		this.deleteLastIncomingChat();
		this.setState({isConfirmInProgress: false});
	}

	deleteLastIncomingChat() {
		if (this.props.incomingChats.length > 0) {
			this.props.deleteIncomingChats([this.props.incomingChats[0].channel]);
		}
	}

	render() {
		return null;
	}

}

Incoming.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		i18n: state.application.i18n,
		configuration: state.configuration,
		incomingChats: state.application.incomingChats,
		openedChannels: state.ui.openedChannels
	}),
	{ focusChannel, openChannel, ignoreChannel, deleteIncomingChats, unreadAdd, confirm, notify }
)(Incoming);