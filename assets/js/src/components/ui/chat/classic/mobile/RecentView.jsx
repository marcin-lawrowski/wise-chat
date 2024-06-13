import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { setMobileTopTab, setMobileTitle } from "actions/ui";
import RecentArea from "components/ui/common/recent/RecentArea";

class RecentView extends React.Component {

	constructor(props) {
		super(props);

		this.handleChannelClick = this.handleChannelClick.bind(this);
	}

	handleChannelClick(channel) {
		this.props.setMobileTopTab('channel');
		this.props.setMobileTitle(channel.name);
	}

	render() {
		return(
			<RecentArea onClick={ recentChat => this.handleChannelClick(recentChat.channel) } />
		)
	}

}

RecentView.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration
	}),
	{ setMobileTopTab, setMobileTitle }
)(RecentView);