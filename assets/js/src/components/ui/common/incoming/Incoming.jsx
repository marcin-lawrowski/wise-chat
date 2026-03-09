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
				if (this.state.isConfirmInProgress === false && !this.props.openedChannels.includes(incoming.channelId)) {
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
						const channelId = incomingChat.channelId;

						if (!this.props.openedChannels.includes(channelId)) {
							this.props.openChannel(channelId);
							if (this.props.configuration.interface.incoming.focus) {
								this.props.focusChannel(channelId);
							} else {
								this.props.unreadAdd(channelId, 1);
							}
							this.props.notify('newChat');
						}
					});

				// mark the incoming chat handled:
				this.props.deleteIncomingChats(this.props.incomingChats.map( incomingChat => incomingChat.channelId ));
			}
		}
	}

	incomingIgnored() {
		this.props.ignoreChannel(this.props.incomingChats[0].channelId);
		this.deleteLastIncomingChat();
		this.setState({isConfirmInProgress: false});
	}

	incomingConfirmed() {
		const channelId = this.props.incomingChats[0].channelId;
		if (!this.props.openedChannels.includes(channelId)) {
			this.props.openChannel(channelId);
		}

		if (this.props.configuration.interface.incoming.focus) {
			this.props.focusChannel(channelId);
		} else {
			this.props.unreadAdd(channelId, 1);
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
			this.props.deleteIncomingChats([this.props.incomingChats[0].channelId]);
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