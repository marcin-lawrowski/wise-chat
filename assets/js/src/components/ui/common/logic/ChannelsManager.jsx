import React from "react";
import { connect } from "react-redux";
import { focusChannel, openChannel, stopIgnoringChannel, confirm, clearChannelOpeningRequest } from "actions/ui";
import { logError } from "actions/log";
import { replaceMessages } from "actions/messages";
import { addChannel } from "actions/application";

class ChannelsManager extends React.Component {

	componentDidUpdate(prevProps) {
		const requested = this.props.channelOpeningRequest !== prevProps.channelOpeningRequest && this.props.channelOpeningRequest;
		const channelOpen = this.props.openChannelCommand !== prevProps.openChannelCommand && this.props.openChannelCommand && this.props.openChannelCommand.success === true;

		if (requested) {
			if (!this.props.configuration.interface.channel.directEnabled) {
				return;
			}
			const channelId = this.props.channelOpeningRequest;
			this.props.clearChannelOpeningRequest();

			// todo: check channel exist
			const channel = this.props.channels.find( channel => channel.id === channelId);
			if (!channel) {
				this.props.logError('Requested channel does not exist: ' + channelId);
				return;
			}

			// display a confirmation if the channel is ignored:
			if (this.props.ignoredChannels.includes(channelId)) {
				this.props.confirm(this.props.i18n.ignoredInfo, function() {
					this.props.stopIgnoringChannel(channelId);
					this.props.openChannel(channelId);
					this.props.focusChannel(channelId);
				}.bind(this));
			} else if (!channel.locked) {
				this.props.openChannel(channelId);
				this.props.focusChannel(channelId);
			}
		}

		// refresh all message in the channel after opening it and store the channel:
		if (channelOpen && this.props.openChannelCommand.result.messages) {
			this.props.addChannel(this.props.openChannelCommand.result.channel, true);
			this.props.replaceMessages(this.props.openChannelCommand.result.parameters.channelId, this.props.openChannelCommand.result.messages);
		}
	}

	render() {
		return null;
	}

}

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18n: state.application.i18n,
		channels: state.application.channels,
		ignoredChannels: state.ui.ignoredChannels,
		focusedChannel: state.ui.focusedChannel,
		channelOpeningRequest: state.ui.channelOpeningRequest,
		openChannelCommand: state.commands.sent.openChannel
	}),
	{ focusChannel, openChannel, stopIgnoringChannel, confirm, clearChannelOpeningRequest, logError, replaceMessages, addChannel }
)(ChannelsManager);

