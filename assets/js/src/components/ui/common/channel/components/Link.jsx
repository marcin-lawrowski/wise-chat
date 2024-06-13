import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { focusChannel, openChannel, stopIgnoringChannel, confirm } from "actions/ui";
import { addChannel } from "actions/application";

class Link extends React.Component {

	constructor(props) {
		super(props);

		this.handleChannelClick = this.handleChannelClick.bind(this);
	}

	handleChannelClick(e) {
		if (this.props.channel.url) {
			return;
		}
		e.preventDefault();

		if (!this.props.configuration.interface.channel.directEnabled) {
			return;
		}

		this.props.addChannel(this.props.channel);

		// display a confirmation if the channel is ignored:
		const channelId = this.props.channel.id;
		if (this.props.ignoredChannels.includes(channelId)) {
			this.props.confirm(this.props.i18n.ignoredInfo, function() {
				this.props.stopIgnoringChannel(channelId);
				this.props.openChannel(channelId);
				this.props.focusChannel(channelId);

				if (this.props.onClick) {
					this.props.onClick(this.props.channel);
				}
			}.bind(this));
		} else if (!this.props.channel.locked) {
			this.props.openChannel(channelId);
			this.props.focusChannel(channelId);

			if (this.props.onClick) {
				this.props.onClick(this.props.channel);
			}
		}
	}

	render() {
		return(
			<a
				href={ this.props.channel.url ? this.props.channel.url : '#'}
				onClick={ e => this.handleChannelClick(e) }
				ref={ this.props.forwardedRef }
				target='_blank'
				rel='noopener noreferrer nofollow'
				className={ this.props.className }
				onMouseEnter={ this.props.onMouseEnter }
				onMouseLeave={ this.props.onMouseLeave }
				onFocus={ this.props.onFocus }
				onBlur={ this.props.onBlur }
				style={ this.props.style }
			>
				{ this.props.children }
			</a>
		)
	}

}

Link.propTypes = {
	channel: PropTypes.object.isRequired,
	forwardedRef: PropTypes.object,
	className: PropTypes.string,
	onMouseEnter: PropTypes.func,
	onMouseLeave: PropTypes.func,
	onFocus: PropTypes.func,
	onBlur: PropTypes.func,
	style: PropTypes.object
};

export default connect(
	state => ({
		configuration: state.configuration,
		i18n: state.application.i18n,
		ignoredChannels: state.ui.ignoredChannels
	}),
	{ focusChannel, openChannel, stopIgnoringChannel, confirm, addChannel }
)(Link);