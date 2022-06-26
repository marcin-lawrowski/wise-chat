import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { alertError, alertInfo } from "actions/ui";
import { sendAuth, clearAuth } from "actions/auth";
import { markChannelAuthorized } from "actions/application";
import { loadPastMessages } from "actions/messages";

class AuthChannelPassword extends React.Component {

	get AUTH_MODE() {
		return 'channel-password';
	}

	constructor(props) {
		super(props);

		this.state = {
			password: ''
		};

		this.handleAuth = this.handleAuth.bind(this);
	}

	componentDidUpdate(prevProps) {
		const authSuccess = this.props.authResult !== prevProps.authResult && this.props.authResult && this.props.authResult.success === true;
		const authFailure = this.props.authResult !== prevProps.authResult && this.props.authResult && this.props.authResult.success === false;

		if (authFailure) {
			this.props.alertError(this.props.authResult.error);
			this.props.clearAuth(this.AUTH_MODE);
		}
		if (authSuccess) {
			this.props.markChannelAuthorized(this.props.channel.id);
			this.props.loadPastMessages(this.props.channel.id);
			this.props.clearAuth(this.AUTH_MODE);
		}
	}

	handleAuth() {
		if (this.state.password.length === 0) {
			this.props.alertError(this.props.i18n.enterPassword);
		} else {
			this.props.sendAuth(this.AUTH_MODE, {
				password: this.state.password,
				channelId: this.props.channel.id
			});
		}
	}

	render() {
		return(
			<div className="wcAuthForm wcAuthChannelPassword">
				<div className="wcHint">{ this.props.i18n.enterPassword }</div>

				<div className="wcFormRow">
					<input
						type="password"
						className="wcInputText wcUserName"
						value={ this.state.password }
						onChange={ e => this.setState({ password: e.currentTarget.value })}
						disabled={ this.props.authResult && this.props.authResult.inProgress }
					/>
					<button
						type="button"
						className="wcButton"
						onClick={ this.handleAuth }
						disabled={ this.props.authResult && this.props.authResult.inProgress }
					>{ this.props.i18nBase.logIn }</button>
				</div>
			</div>
		)
	}

}

AuthChannelPassword.propTypes = {
	channel: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		i18nBase: state.configuration.i18n,
		i18n: state.application.i18n,
		authResult: state.auth.sent['channel-password']
	}),
	{ alertError, alertInfo, sendAuth, clearAuth, markChannelAuthorized, loadPastMessages }
)(AuthChannelPassword);