import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { sendMessage } from "actions/messages";
import { alertError, alertInfo, toastInfo } from "actions/ui";
import { sendUserCommand, clearUserCommand } from "actions/commands";
import ColorPopup from "./ColorPopup";

class CustomizeArea extends React.Component {

	get COMMAND_ID() {
		return 'customize';
	}

	constructor(props) {
		super(props);

		this.state = this.mapPropsToState();

		this.handlePropertySet = this.handlePropertySet.bind(this);
		this.handleNameSave = this.handleNameSave.bind(this);
	}

	componentDidUpdate(prevProps) {
		const userLoaded = this.props.user !== prevProps.user && this.props.user;
		const commandFailure = this.props.command !== prevProps.command && this.props.command && this.props.command.success === false;
		const commandSuccess = this.props.command !== prevProps.command && this.props.command && this.props.command.success === true;

		if (userLoaded && !this.props.visible) {
			this.setState(this.mapPropsToState());
		}

		if (commandSuccess) {
			if (this.props.onSave) {
				this.props.onSave();
			}
			this.props.toastInfo(this.props.i18n.savedSettings);
		}

		if (commandFailure) {
			this.props.alertError(this.props.command.error);
			this.props.clearUserCommand(this.COMMAND_ID);
		}
	}

	mapPropsToState() {
		return {
			name: this.props.user.name,
			muteSounds: this.props.user.settings.muteSounds,
			textColor: this.props.user.settings.textColor
		}
	}

	handlePropertySet(property, value) {
		this.setState({ [property]: value });
		this.sendProperty(property, value);
	}

	handleNameSave() {
		if (this.state.name.length === 0) {
			this.props.alertError(this.props.i18n.enterYourUsername);
		} else {
			this.sendProperty('name', this.state.name);
		}
	}

	sendProperty(property, value) {
		this.props.sendUserCommand(this.COMMAND_ID, 'setUserProperty', { property: property, value: value });
	}

	render() {
		if (!this.props.visible) {
			return null;
		}

		return(
			<div className="wcCustomizationsPanel">
				{this.props.user.settings.allowChangeUserName &&
					<div className="wcProperty">
						<label>
							{ this.props.i18n.name }:&nbsp;
							<input
								className="wcUserName"
								type="text"
								maxLength={ this.props.configuration.interface.customization.userNameLengthLimit }
								value={ this.state.name }
								onChange={ e => this.setState({ name: e.target.value })}
							/>
						</label>
						<button className="wcUserNameApprove" type="button" onClick={ this.handleNameSave }>
							{ this.props.i18n.save }
						</button>
					</div>
				}

				{this.props.user.settings.allowMuteSound &&
					<div className="wcProperty">
						<label>
							{this.props.i18n.muteSounds}
							<input
								className="wcMuteSound"
								type="checkbox"
								checked={this.state.muteSounds}
								onChange={e => this.handlePropertySet('muteSounds', e.target.checked)}
							/>
						</label>
					</div>
				}

				{ this.props.user.settings.allowChangeTextColor &&
					<div id="color" className="wcProperty">
						<label>{ this.props.i18n.textColor }: </label>
						<ColorPopup color={ this.state.textColor } onSelect={ color => this.handlePropertySet('textColor', color) } />
						<button className="wcTextColorReset" type="button" onClick={ e => this.handlePropertySet('textColor', null) }>
							{ this.props.i18n.reset }
						</button>
					</div>
				}
			</div>
		)
	}

}

CustomizeArea.propTypes = {
	configuration: PropTypes.object.isRequired,
	visible: PropTypes.bool.isRequired,
	onSave: PropTypes.func
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		user: state.application.user,
		i18n: state.application.i18n,
		i18nBase: state.configuration.i18n,
		command: state.commands.sent['customize']
	}),
	{ sendMessage, alertError, alertInfo, toastInfo, sendUserCommand, clearUserCommand }
)(CustomizeArea);