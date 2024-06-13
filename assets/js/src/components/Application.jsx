import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Chat from 'ui/chat/Chat';
import Engine from "engine/Engine";
import PresenceChecker from "./ui/common/PresenceChecker";

class Application extends React.Component {

	render() {
		return(
			<React.Fragment>
				<Chat engine={ this.props.engine } />
				<PresenceChecker rootElement={ this.props.rootElement } />
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
		configuration: state.configuration
	})
)(Application);