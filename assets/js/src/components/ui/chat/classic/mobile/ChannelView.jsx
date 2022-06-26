import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Channel from "ui/common/channel/Channel";

class ChannelView extends React.Component {

	render() {
		const openedChannels = this.props.channels.filter( channel => this.props.openedChannels.includes(channel.id) );

		return(
			<React.Fragment>
				{ openedChannels.map( channel =>
					<div key={ channel.id } className={ "wcChannelContainer" + (channel.id !== this.props.focusedChannel ? ' wcInvisible' : '') }>
						<Channel channel={ channel } />
					</div>
				)}
				{ this.props.channels.length > 0 && !openedChannels.find( channel => channel.id === this.props.focusedChannel ) &&
					<div className="wcChannelContainer wcChannelContainerEmpty">
						<span className="wcEmptyChannel">{ this.props.i18nBase.noChannels }</span>
					</div>
				}
			</React.Fragment>
		)
	}

}

ChannelView.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18nBase: state.configuration.i18n,
		channels: state.application.channels,
		focusedChannel: state.ui.focusedChannel,
		openedChannels: state.ui.openedChannels
	})
)(ChannelView);