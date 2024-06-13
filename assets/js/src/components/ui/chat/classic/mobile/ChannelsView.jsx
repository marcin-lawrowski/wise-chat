import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { Scrollbar } from "react-scrollbars-custom";
import { focusChannel, openChannel, setMobileTopTab, setMobileTitle } from "actions/ui";

class ChannelsView extends React.Component {

	constructor(props) {
		super(props);

		this.handleChannelClick = this.handleChannelClick.bind(this);
	}

	handleChannelClick(channel) {
		if (!channel.locked) {
			this.props.openChannel(channel.id);
			this.props.focusChannel(channel.id);
			this.props.setMobileTopTab('channel');
			this.props.setMobileTitle(channel.name);
		}
	}

	getPublicChannelClasses(channel) {
		const classes = ["wcChannelEntry"];
		if (channel.protected === true && channel.authorized === false) {
			classes.push('wcLockedChannel');
		}
		if (channel.protected === true && channel.authorized === true) {
			classes.push('wcUnLockedChannel');
		}

		return classes.join(' ');
	}

	render() {
		return(
			<Scrollbar>
				{ this.props.publicChannels.map( channel =>
					<div key={ channel.id } className={ this.getPublicChannelClasses(channel) } onClick={ e => this.handleChannelClick(channel) }>
						<img src={ channel.avatar ? channel.avatar : '' } className="wcFunctional wcChannelAvatar" alt={ channel.name } />
						<span className="wcName">{ channel.name }</span>
					</div>
				) }
			</Scrollbar>
		)
	}

}

ChannelsView.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		publicChannels: state.application.publicChannels
	}),
	{ focusChannel, openChannel, setMobileTopTab, setMobileTitle }
)(ChannelsView);