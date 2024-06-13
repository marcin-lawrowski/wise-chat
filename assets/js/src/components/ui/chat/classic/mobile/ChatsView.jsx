import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { focusChannel, setMobileTopTab, setMobileTitle } from "actions/ui";
import {Scrollbar} from "react-scrollbars-custom";
import CloseButton from "ui/common/channel/components/CloseButton";

class ChatsView extends React.Component {

	constructor(props) {
		super(props);

		this.handleChannelClick = this.handleChannelClick.bind(this);
	}

	handleChannelClick(channel) {
		this.props.focusChannel(channel.id);
		this.props.setMobileTopTab('channel');
		this.props.setMobileTitle(channel.name);
	}

	render() {
		const openedChannels = this.props.channels.filter( channel => this.props.openedChannels.includes(channel.id) );

		if (openedChannels.length === 0) {
			return (
				<div className="wcCenter">
					<span className="wcNoChats">{ this.props.i18nBase.noChats }</span>
				</div>
			)
		}

		return(
			<Scrollbar>
				{ openedChannels.map( channel =>
					<div key={ channel.id } className="wcChannelEntry" onClick={ e => this.handleChannelClick(channel) }>
						<div className="wcDetails">
							{channel.avatar &&
								<img src={channel.avatar} className="wcFunctional wcChannelAvatar" alt={channel.name} />
							}
							<span className="wcName">{ channel.name } { this.props.channelsUi[channel.id] && this.props.channelsUi[channel.id].unread > 0 ? <span className="wcUnread">*</span> : '' }</span>
						</div>
						{ (this.props.publicChannels.length > 0 || channel.type === 'direct') && <CloseButton channel={channel} /> }
					</div>
				)}
			</Scrollbar>
		)

	}

}

ChatsView.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18nBase: state.configuration.i18n,
		publicChannels: state.application.publicChannels,
		channels: state.application.channels,
		openedChannels: state.ui.openedChannels,
		channelsUi: state.ui.channels
	}),
	{ focusChannel, setMobileTopTab, setMobileTitle }
)(ChatsView);