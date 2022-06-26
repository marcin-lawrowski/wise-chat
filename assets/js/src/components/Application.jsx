import React from "react";
import PropTypes from 'prop-types';
import Chat from 'ui/chat/Chat';
import Engine from "engine/Engine";

class Application extends React.Component {

	render() {
		return(
			<Chat engine={ this.props.engine } />
		)
	}

}

Application.propTypes = {
	engine: PropTypes.instanceOf(Engine).isRequired
};

export default Application;