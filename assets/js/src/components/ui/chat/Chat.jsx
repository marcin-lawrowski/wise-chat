import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { restoreChannels, minimizeChannels, completeInit, updateProperties } from "actions/ui";
import Engine from "engine/Engine";
import ClassicChat from "./classic/ClassicChat";
import Alerts from "ui/common/alerts/Alerts";
import Confirms from "ui/common/alerts/Confirms";
import Incoming from "ui/common/incoming/Incoming";
import UserSession from "ui/common/session/UserSession";
import Notifications from "ui/common/notifications/Notifications";
import ChannelsManager from "../common/logic/ChannelsManager";
import $ from "jquery";

class Chat extends React.Component {

	constructor(props) {
		super(props);

		this.handleResize = this.handleResize.bind(this);
	}

	componentDidMount() {
		window.addEventListener('resize', this.handleResize);
		this.handleResize();
		this.props.engine.start();
	}

	componentWillUnmount() {
		window.removeEventListener('resize', this.handleResize);
		this.props.engine.stop();
	}

	handleResize() {
		const width = $(window).width();

		let sizeClass = 'Xl';
		if (width < 380) {
			sizeClass = 'XXs';
		} else if (width < 576) {
			sizeClass = 'Xs';
		} else if (width < 768) {
			sizeClass = 'Sm';
		} else if (width < 992) {
			sizeClass = 'Md';
		} else if (width < 1200) {
			sizeClass = 'Lg';
		}

		this.props.updateProperties({
			windowWidth: width,
			windowSizeClass: sizeClass,
			isMobile: ['Xs', 'XXs'].includes(sizeClass)
		});
	}


	componentDidUpdate(prevProps) {
		const userLoaded = this.props.user !== prevProps.user && this.props.user;
		const authLoaded = this.props.auth !== prevProps.auth && this.props.auth;

		// restore saved channels
		if (userLoaded && (!prevProps.user || prevProps.user.id !== this.props.user.id)) {
			this.props.restoreChannels();
			this.props.completeInit();
		}
	}

	render() {
		return(
			<React.Fragment>
				{ this.props.configuration.mode === 0 &&
					<ClassicChat />
				}
				<ChannelsManager />
				<Alerts />
				<Confirms />
				{ this.props.configuration.interface.incoming.enabled && <Incoming /> }
				<Notifications />
				<UserSession />
			</React.Fragment>
		)
	}

}

Chat.propTypes = {
	configuration: PropTypes.object.isRequired,
	engine: PropTypes.instanceOf(Engine).isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		user: state.application.user,
		auth: state.application.auth,
		openedChannels: state.ui.openedChannels
	}),
	{ restoreChannels, minimizeChannels, completeInit, updateProperties }
)(Chat);