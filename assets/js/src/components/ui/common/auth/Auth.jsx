import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import AccessDenied from "./AccessDenied";
import AuthUserName from "./AuthUserName";

class Auth extends React.Component {

	render() {
		return(
			<div className="wcAuthContainer" style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				{ this.props.titleVisible && this.props.configuration.interface.chat.title &&
					<div className="wcTitle">
						{ this.props.configuration.interface.chat.title }
					</div>
				}
				<div className="wcAuth">
					{ this.props.auth.mode === 'access-denied' && <AccessDenied /> }
					{ this.props.auth.mode === 'auth-username' && <AuthUserName /> }
				</div>
			</div>
		)
	}

}

Auth.defaultProps = {
	titleVisible: true
};

Auth.propTypes = {
	configuration: PropTypes.object.isRequired,
	auth: PropTypes.object.isRequired,
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		auth: state.application.auth
	})
)(Auth);