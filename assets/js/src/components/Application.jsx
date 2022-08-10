import React from "react";
import PropTypes from 'prop-types';
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
	engine: PropTypes.instanceOf(Engine).isRequired
};

export default Application;