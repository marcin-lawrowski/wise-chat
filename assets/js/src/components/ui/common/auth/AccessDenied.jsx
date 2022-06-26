import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";

class AccessDenied extends React.Component {

	render() {
		return(
			<div className="wcErrorBox wcAccessDenied">
				{ this.props.auth.error }
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