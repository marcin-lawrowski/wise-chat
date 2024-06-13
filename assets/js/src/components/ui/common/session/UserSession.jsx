import React from "react";
import { connect } from "react-redux";
import { sendUserCommand, clearUserCommand } from "actions/commands";
import { alertError, clear as clearUI } from "actions/ui";
import { clear as clearApplication, refreshAuthenticationData } from "actions/application";
import { clear as clearMessages } from "actions/messages";

class UserSession extends React.Component {

	get COMMAND_ID() {
		return 'logOff';
	}

	componentDidUpdate(prevProps) {
		const logOffRequested = this.props.logOffRequest !== prevProps.logOffRequest && this.props.logOffRequest;
		const commandFailure = this.props.command !== prevProps.command && this.props.command && this.props.command.success === false;
		const commandSuccess = this.props.command !== prevProps.command && this.props.command && this.props.command.success === true;

		if (logOffRequested) {
			this.props.sendUserCommand(this.COMMAND_ID, 'logOff', { now: true });
		}

		if (commandFailure) {
			this.props.alertError(this.props.command.error);
			this.props.clearUserCommand(this.COMMAND_ID);
		}

		if (commandSuccess) {
			const commandResult = this.props.command.result;
			this.props.clearUserCommand(this.COMMAND_ID);

			// clear all current state:
			this.props.clearUI();
			this.props.clearApplication();
			this.props.clearMessages();
			this.props.refreshAuthenticationData();
		}
	}

	componentWillUnmount() {
		this.props.clearUserCommand(this.COMMAND_ID);
	}

	render() {
		return null;
	}

}

export default connect(
	state => ({
		command: state.commands.sent.logOff,
		logOffRequest: state.ui.logOffRequest
	}),
	{ alertError, sendUserCommand, clearUserCommand, clearUI, clearApplication, clearMessages, refreshAuthenticationData }
)(UserSession);