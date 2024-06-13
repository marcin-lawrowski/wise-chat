import React from "react";
import PropTypes from "prop-types";
import {connect} from "react-redux";
import { focusChannel, closeChannel, confirm, logOff } from "actions/ui";

class CloseButton extends React.Component {

	constructor(props) {
		super(props);

		this.handleCloseClick = this.handleCloseClick.bind(this);
		this.closeChannel = this.closeChannel.bind(this);
	}

	handleCloseClick(e) {
		e.preventDefault();
		e.stopPropagation();

		if (this.props.configuration.interface.channel.direct.closeConfirmation) {
			this.props.confirm(this.props.i18n.directChannelCloseConfirmation, this.closeChannel);
		} else {
			if (this.props.stream) {
				this.props.confirm(this.props.i18n.directChannelWithStreamCloseConfirmation, this.closeChannel);
			} else {
				this.closeChannel();
			}
		}
	}

	/**
	 * @see Please note that this.props.openedChannels may have outdated data, cannot rely directly on it
	 */
	closeChannel() {
		let closedLast = false;

		if (this.props.focusedChannel === this.props.channel.id && this.props.focusOnClose) {
			const currentIndex = this.props.openedChannels.indexOf(this.props.channel.id);
			let nextChannel;

			if (currentIndex === (this.props.openedChannels.length - 1)) {
				// if this is the last channel then open the predecessor:
				nextChannel = this.getLastChannelBeforeIndex(currentIndex);
			} else {
				// otherwise pick the successor:
				nextChannel = this.getFirstChannelAfterIndex(currentIndex);
				if (!nextChannel) {
					nextChannel = this.getLastChannelBeforeIndex(currentIndex);
				}
			}

			this.props.closeChannel(this.props.channel.id);
			this.props.focusChannel(nextChannel ? nextChannel : undefined);
			if (!nextChannel) {
				closedLast = true;
			}
		} else {
			this.props.closeChannel(this.props.channel.id);
		}

		if (this.props.configuration.interface.channel.logOffOnCloseLast && closedLast) {
			this.props.logOff();
		}
	}

	getFirstChannelAfterIndex(afterIndex) {
		return this.props.openedChannels
			.find( (openedChannelId, index) => index > afterIndex && this.props.channels.find( channel => channel.id === openedChannelId ) );
	}

	getLastChannelBeforeIndex(beforeIndex) {
		let found = this.props.openedChannels
			.filter( (openedChannelId, index) => index < beforeIndex && this.props.channels.find( channel => channel.id === openedChannelId ) );

		return found.length > 0 ? found[found.length - 1] : undefined;
	}

	render() {
		return <a href="#" onClick={ this.handleCloseClick } title={ this.props.i18n.close } className={ this.props.className } />
	}

}

CloseButton.defaultProps = {
	className: 'wcFunctional wcChannelClose',
	focusOnClose: false
}

CloseButton.propTypes = {
	channel: PropTypes.object.isRequired,
	className: PropTypes.string.isRequired,
	focusOnClose: PropTypes.bool.isRequired,
	onClose: PropTypes.func
};

export default connect(
	(state, ownProps) => ({
		i18n: state.application.i18n,
		configuration: state.configuration,
		channels: state.application.channels,
		openedChannels: state.ui.openedChannels,
		focusedChannel: state.ui.focusedChannel,
		stream: state.ui.streams.find( stream => stream.channel && stream.channel.id === ownProps.channel.id )
	}), { focusChannel, closeChannel, confirm, logOff }
)(CloseButton);