import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Decorator from "../plain/Decorator";

class AccessDenied extends React.Component {

	render() {
		return(
			<div className="wcErrorBox wcAccessDenied">
				<Decorator>{ this.props.auth.error }</Decorator>
			</div>
		)
	}

}

AccessDenied.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		auth: state.application.auth
	})
)(AccessDenied);