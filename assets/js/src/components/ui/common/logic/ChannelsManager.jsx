import React from "react";
import { connect } from "react-redux";
import { focusChannel, openChannel, stopIgnoringChannel, confirm, clearChannelOpeningRequest } from "actions/ui";
import { logError } from "actions/log";

class ChannelsManager extends React.Component {

	componentDidUpdate(prevProps) {
		const requested = this.props.channelOpeningRequest !== prevProps.channelOpeningRequest && this.props.channelOpeningRequest;

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
		channelOpeningRequest: state.ui.channelOpeningRequest
	}),
	{ focusChannel, openChannel, stopIgnoringChannel, confirm, clearChannelOpeningRequest, logError }
)(ChannelsManager);