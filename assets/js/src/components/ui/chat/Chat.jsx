import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { focusChannel, openChannel, completeInit } from "actions/ui";
import Engine from "engine/Engine";
import ClassicChat from "./classic/ClassicChat";
import Alerts from "ui/common/alerts/Alerts";
import Confirms from "ui/common/alerts/Confirms";
import Notifications from "ui/common/notifications/Notifications";

class Chat extends React.Component {

	componentDidMount() {
		this.props.engine.start();
	}

	componentWillUnmount() {
		this.props.engine.stop();
	}

	componentDidUpdate(prevProps) {
		const userLoaded = this.props.user !== prevProps.user && this.props.user;
		const domDestroyed = this.props.domPresent !== prevProps.domPresent && this.props.domPresent === false;

		// restore saved channels
		if (userLoaded && (!prevProps.user || prevProps.user.id !== this.props.user.id)) {
			this.autoOpenChannels();
			this.props.completeInit();
		}
		if (domDestroyed) {
			this.props.engine.stop();
		}
	}

	autoOpenChannels() {
		if (this.props.publicChannels.length > 0) {
			const publicChannel = this.props.publicChannels[0].id;
			this.props.focusChannel(publicChannel);
			this.props.openChannel(publicChannel);
		}
	}

	render() {
		return(
			<React.Fragment>
				<ClassicChat />
				<Alerts />
				<Confirms />
				<Notifications />
			</React.Fragment>
		)
	}

}

Chat.propTypes = {
	engine: PropTypes.instanceOf(Engine).isRequired
};

export default connect(
	(state) => ({
		user: state.application.user,
		publicChannels: state.application.publicChannels,
		openedChannels: state.ui.openedChannels,
		domPresent: state.application.domPresent
	}),
	{ focusChannel, openChannel, completeInit }
)(Chat);