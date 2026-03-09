import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { alertError, alertInfo } from "actions/ui";
import { sendAuth, clearAuth } from "actions/auth";
import { refreshAuthenticationData } from "actions/application";
import Decorator from "ui/common/channel/messages/message/Decorator";

class AuthUserName extends React.Component {

	get AUTH_MODE() {
		return 'username';
	}

	constructor(props) {
		super(props);

		this.state = {
			input: '',
			fields: this.props.configuration.interface.auth.username.fields.reduce((previous, current) => ({ ...previous, [current.id]: ''  }), {})
		};

		this.handleAuth = this.handleAuth.bind(this);
		this.renderField = this.renderField.bind(this);
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
				fields: this.state.fields,
				nonce: this.props.auth.nonce
			});
		}
	}

	renderField(field) {
		switch (field.type) {
			case 'text':
				return <input
					id={ 'wcAuthField' + field.id }
					type="text"
					className="wcControl wcInputText"
					value={ this.state.fields[field.id] }
					onChange={ e => this.setState({ fields: { ...this.state.fields, [field.id]: e.currentTarget.value }} )}
					disabled={ this.props.authResult && this.props.authResult.inProgress }
				/>
			case 'long_text':
				return <textarea
					id={ 'wcAuthField' + field.id }
					className="wcControl wcInputText"
					value={ this.state.fields[field.id] }
					onChange={ e => this.setState({ fields: { ...this.state.fields, [field.id]: e.currentTarget.value }} )}
					disabled={ this.props.authResult && this.props.authResult.inProgress }
				/>
		}

		return null;
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
		const { fields, intro } = this.props.configuration.interface.auth.username;

		return(
			<div className="wcAuthForm wcAuthUserName">
				{ intro &&  <div className="wcIntroContent"><Decorator>{ intro }</Decorator></div> }
				<div className="wcAuthFieldContainer">
					<label htmlFor="wcAuthFieldUserName">{ this.props.configuration.i18n.enterUserName }</label>
					<div className="wcFormRow">
						<input
							id="wcAuthFieldUserName"
							type="text"
							className="wcControl wcInputText wcUserName"
							value={ this.state.input }
							onChange={ e => this.setState({ input: e.currentTarget.value })}
							disabled={ this.props.authResult && this.props.authResult.inProgress }
							maxLength={ this.props.configuration.interface.customization.userNameLengthLimit }
						/>
						{ fields.length === 0 && this.renderAuthButton() }
					</div>
				</div>

				{ fields.map( (field, index) =>
					<div key={ index } className={ 'wcAuthFieldContainer wcAuthFieldContainer' + field.id }>
						<label htmlFor={ 'wcAuthField' + field.id }>{ field.name }</label>
						{ this.renderField(field) }
					</div>
				)}

				{ fields.length > 0 && this.renderAuthButton() }

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