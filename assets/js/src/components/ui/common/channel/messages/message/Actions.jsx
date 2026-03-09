import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { confirm, alertError, alertInfo, setMessageReplyTo } from "actions/ui";
import { sendUserCommand, clearUserCommand } from "actions/commands";
import { deleteMessage, refreshMessage } from "actions/messages";
import Popup from 'reactjs-popup';

class Actions extends React.Component {

	constructor(props) {
		super(props);

		this.handleAction = this.handleAction.bind(this);
		this.handleDeleteConfirmed = this.handleDeleteConfirmed.bind(this);
		this.handleBanConfirmed = this.handleBanConfirmed.bind(this);
		this.handleMuteConfirmed = this.handleMuteConfirmed.bind(this);
		this.handleUnMuteConfirmed = this.handleUnMuteConfirmed.bind(this);
		this.handleSpamReportConfirmed = this.handleSpamReportConfirmed.bind(this);
	}

	componentDidUpdate(prevProps) {
		const commandFailure = this.props.command !== prevProps.command && this.props.command && this.props.command.success === false;
		const commandSuccess = this.props.command !== prevProps.command && this.props.command && this.props.command.success === true;

		if (commandFailure) {
			this.props.alertError(this.props.command.error);
			this.props.clearUserCommand(this.props.message.id);
		}

		if (commandSuccess) {
			const commandResult = this.props.command.result;
			this.props.clearUserCommand(this.props.message.id);

			if (commandResult.command === 'deleteMessage') {
				this.props.deleteMessage(commandResult.parameters.id, commandResult.parameters.channel.id);
			}
			if (commandResult.command === 'banUser') {
				this.props.alertInfo(this.props.i18n.banConfirmed);
			}
			if (commandResult.command === 'muteUser') {
				this.props.alertInfo(this.props.i18n.muteConfirmed);
			}
			if (commandResult.command === 'unMuteUser') {
				this.props.alertInfo(this.props.i18n.unMuteConfirmed);
			}
			if (commandResult.command === 'reportSpam') {
				this.props.alertInfo(this.props.i18n.spamReportConfirmed);
			}
		}
	}

	handleAction(e, action) {
		e.preventDefault();

		switch (action) {
			case 'delete':
				this.props.confirm(this.props.i18n.deleteConfirmation, this.handleDeleteConfirmed);
				break;
			case 'mute':
				this.props.confirm(this.props.i18n.muteConfirmation, this.handleMuteConfirmed);
				break;
			case 'unmute':
				this.props.confirm(this.props.i18n.unMuteConfirmation, this.handleUnMuteConfirmed);
				break;
			case 'ban':
				this.props.confirm(this.props.i18n.banConfirmation, this.handleBanConfirmed);
				break;
			case 'spam':
				this.props.confirm(this.props.i18n.spamReportConfirmation, this.handleSpamReportConfirmed);
				break;
			case 'reply':
				this.props.setMessageReplyTo(this.props.message.id, this.props.message.channel.id);
				break;
		}
	}

	handleDeleteConfirmed() {
		this.props.sendUserCommand(this.props.message.id, 'deleteMessage', { id: this.props.message.id, channel: this.props.message.channel });
	}

	handleMuteConfirmed() {
		this.props.sendUserCommand(this.props.message.id, 'muteUser', { id: this.props.message.id, channel: this.props.message.channel });
	}

	handleUnMuteConfirmed() {
		this.props.sendUserCommand(this.props.message.id, 'unMuteUser', { id: this.props.message.id, channel: this.props.message.channel });
	}

	handleBanConfirmed() {
		this.props.sendUserCommand(this.props.message.id, 'banUser', { id: this.props.message.id, channel: this.props.message.channel });
	}

	handleSpamReportConfirmed() {
		this.props.sendUserCommand(this.props.message.id, 'reportSpam', { id: this.props.message.id, channel: this.props.message.channel, url: window.location.href });
	}

	getRights() {
		let rights = [];

		if (this.props.userRights.banUsers) {
			rights.push('ban');
		}
		if (this.props.userRights.deleteMessages || (this.props.message.own && this.props.userRights.deleteOwnMessages)) {
			rights.push('delete');
		}
		if (this.props.userRights.editMessages || (this.props.message.own && this.props.userRights.editOwnMessages)) {
			rights.push('edit');
		}
		if (this.props.userRights.muteUsers) {
			rights.push('mute');
		}
		if (this.props.userRights.replyToMessages) {
			rights.push('reply');
		}
		if (this.props.userRights.spamReport) {
			rights.push('spam');
		}

		return rights;
	}

	render() {
		const rights = this.getRights();

		if (rights.length === 0) {
			return null;
		}

		return(
			<React.Fragment>
				<Popup
					trigger={<button className={ "wcActionsButton wcFunctional" + (this.props.visible ? ' wcActionsButtonVisible' : '') } />}
					position="center center"
					closeOnDocumentClick
					className={ "wcPopup wcActionsPopup " + this.props.configuration.themeClassName }
					open={ this.props.visible === false ? false : undefined }
					contentStyle={ { height: 40 } }
					keepTooltipInside={ '.wcChannel[data-id="' + this.props.channel.id + '"]' }
					arrow={ false }
				>
					{close => (
						<div className="wcActions">
							{rights.includes('delete') &&
								<a href="#" className="wcAction wcDelete wcFunctional" onClick={ e => this.handleAction(e, 'delete') } title={ this.props.i18n.deleteMessage} />
							}
							{rights.includes('edit') &&
								<a href="#" className="wcAction wcEdit wcFunctional" onClick={ e => this.handleAction(e, 'edit') } title={ this.props.i18n.editMessage} />
							}
							{rights.includes('mute') && !this.props.channel.muted &&
								<a href="#" className="wcAction wcMute wcFunctional" onClick={ e => this.handleAction(e, 'mute') } title={ this.props.i18n.muteThisUser} />
							}
							{rights.includes('mute') && this.props.channel.muted &&
								<a href="#" className="wcAction wcUnMute wcFunctional" onClick={ e => this.handleAction(e, 'unmute') } title={ this.props.i18n.unMuteThisUser} />
							}
							{rights.includes('ban') &&
								<a href="#" className="wcAction wcBan wcFunctional" onClick={ e => this.handleAction(e, 'ban') } title={ this.props.i18n.banThisUser} />
							}
							{rights.includes('spam') &&
								<a href="#" className="wcAction wcSpam wcFunctional" onClick={ e => this.handleAction(e, 'spam') } title={ this.props.i18n.reportSpam} />
							}
							{rights.includes('reply') &&
								<a href="#" className="wcAction wcReply wcFunctional" onClick={ e => this.handleAction(e, 'reply') } title={ this.props.i18n.replyToMessage} />
							}
						</div>
					)}
				</Popup>
			</React.Fragment>
		)
	}

}

Actions.propTypes = {
	channel: PropTypes.object.isRequired,
	message: PropTypes.object.isRequired,
	visible: PropTypes.bool.isRequired,
	command: PropTypes.object
};

export default connect(
	(state, ownProps) => ({
		configuration: state.configuration,
		i18n: state.application.i18n,
		userRights: state.application.user.rights,
		command: state.commands.sent[ownProps.message.id]
	}),
	{ alertError, alertInfo, confirm, sendUserCommand, clearUserCommand, deleteMessage, refreshMessage, setMessageReplyTo }
)(Actions);