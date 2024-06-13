import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { alertError, alertInfo } from "actions/ui";
import { sendAuth, clearAuth } from "actions/auth";
import { refreshAuthenticationData } from "actions/application";

class AuthUserName extends React.Component {

	get AUTH_MODE() {
		return 'username';
	}

	constructor(props) {
		super(props);

		this.state = {
			input: ''
		};

		this.handleAuth = this.handleAuth.bind(this);
		this.renderAuthButton = this.renderAuthButton.bind(this);
	}

	componentDidUpdate(prevProps) {
		const authSuccess = this.props.authResult !== prevProps.authResult && this.props.authResult && this.props.authResult.success === true;
		const authFailure = this.props.authResult !== prevProps.authResult && this.props.authResult && this.props.authResult.success === false;

		if (authFailure) {
			this.props.alertError(this.props.authResult.error);
			this.props.clearAuth(this.AUTH_MODE);
		}
		if (authSuccess) {
			this.props.refreshAuthenticationData();
			this.props.clearAuth(this.AUTH_MODE);
		}
	}

	handleAuth() {
		if (this.state.input.length === 0) {
			this.props.alertError(this.props.configuration.i18n.enterUserName);
		} else {
			this.props.sendAuth(this.AUTH_MODE, {
				name: this.state.input,
				fields: [],
				nonce: this.props.auth.nonce
			});
		}
	}

	renderAuthButton() {
		return <button
			type="button"
			className="wcButton"
			onClick={ this.handleAuth }
			disabled={ this.props.authResult && this.props.authResult.inProgress }
		>{ this.props.configuration.i18n.logIn }</button>
	}

	render() {
		return(
			<div className="wcAuthForm wcAuthUserName">
				<div className="wcAuthFieldContainer">
					<label htmlFor="wcAuthFieldUserName">{ this.props.configuration.i18n.enterUserName }</label>
					<div className="wcFormRow">
						<input
							id="wcAuthFieldUserName"
							type="text"
							className="wcInputText wcUserName"
							value={ this.state.input }
							onChange={ e => this.setState({ input: e.currentTarget.value })}
							disabled={ this.props.authResult && this.props.authResult.inProgress }
							maxLength={ this.props.configuration.interface.customization.userNameLengthLimit }
						/>
						{ this.renderAuthButton() }
					</div>
				</div>
			</div>
		)
	}

}

AuthUserName.propTypes = {
	configuration: PropTypes.object.isRequired,
	auth: PropTypes.object.isRequired,
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		auth: state.application.auth,
		authResult: state.auth.sent['username']
	}),
	{ alertError, alertInfo, sendAuth, clearAuth, refreshAuthenticationData }
)(AuthUserName);