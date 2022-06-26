import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Channel from "ui/common/channel/Channel";

class TabbedController extends React.Component {

	getOpenedChannels() {
		return this.props.openedChannels
			.filter( channelId => this.props.channels.find( channel => channel.id === channelId ) )
			.map( channelId => this.props.channels.find( channel => channel.id === channelId) );
	}

	render() {
		const openedChannels = this.getOpenedChannels();

		return(
			<React.Fragment>
				{ openedChannels.map( channel =>
					<div key={ channel.id } className={ "wcTabContent" + (channel.id !== this.props.focusedChannel ? ' wcInvisible' : '') }>
						<Channel channel={ channel } />
					</div>
				)}
				{ this.props.channels.length > 0 && !openedChannels.find( channel => channel.id === this.props.focusedChannel) &&
					<div className="wcTabContent wcTabContentEmpty">
						<span className="wcEmptyChannel">{ this.props.i18nBase.noChannels }</span>
					</div>
				}
			</React.Fragment>
		)
	}

}

TabbedController.propTypes = {
	channels: PropTypes.array.isRequired
};

export default connect(
	(state) => ({
		i18nBase: state.configuration.i18n,
		channels: state.application.channels,
		focusedChannel: state.ui.focusedChannel,
		openedChannels: state.ui.openedChannels
	})
)(TabbedController);