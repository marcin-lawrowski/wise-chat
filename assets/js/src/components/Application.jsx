import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Chat from 'ui/chat/Chat';
import Engine from "engine/Engine";
import PresenceChecker from "./ui/common/PresenceChecker";
import Toasts from "./ui/common/toasts/Toasts";
import ReactDOM from "react-dom";
import $ from "jquery";

class Application extends React.Component {

	componentDidUpdate(prevProps, prevState, snapshot) {
		if (prevProps.destroyRequest !== this.props.destroyRequest) {
			this.props.engine.stop();
			ReactDOM.unmountComponentAtNode(this.props.rootElement);
			$('#' + this.props.configuration.chatId).remove();
			window._wiseChat.instances = window._wiseChat.instances.filter( instance => instance.chatId !== this.props.configuration.chatId );
		}
	}

	render() {
		return(
			<React.Fragment>
				<Chat engine={ this.props.engine } />
				<PresenceChecker rootElement={ this.props.rootElement } />
				<Toasts />
			</React.Fragment>
		)
	}

}

Application.propTypes = {
	configuration: PropTypes.object.isRequired,
	rootElement: PropTypes.object.isRequired,
	engine: PropTypes.instanceOf(Engine).isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		destroyRequest: state.ui.destroyRequest
	})
)(Application);