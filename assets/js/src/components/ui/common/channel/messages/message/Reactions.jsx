import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { confirm, alertError, alertInfo } from "actions/ui";
import { sendUserCommand, clearUserCommand } from "actions/commands";
import Popup from "reactjs-popup";
import Time from "./Time";
import Modal from "ui/common/modal/Modal";
import Spinner from "ui/common/Spinner";
import {Scrollbar} from "react-scrollbars-custom";

class Reactions extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			reactionsModalOpen: false,
			...this.reactionsToState(props.message)
		};

		this.toggleReaction = this.toggleReaction.bind(this);
		this.getClassName = this.getClassName.bind(this);
		this.isAnyReactionActive = this.isAnyReactionActive.bind(this);
		this.loadReactionsLog = this.loadReactionsLog.bind(this);
	}

	componentDidUpdate(prevProps, prevState, snapshot) {
		if (this.props.message !== prevProps.message) {
			this.setState({
				counters: this.props.message.reactions && this.props.message.reactions.counters ? this.props.message.reactions.counters : {}
			});
		}
	}

	reactionsToState(message) {
		return {
			own: message.reactions && message.reactions.own ? message.reactions.own : [],
			counters: message.reactions && message.reactions.counters ? message.reactions.counters : {},
		}
	}

	toggleReaction(e, reactionId) {
		e.preventDefault();

		if (this.isActive(reactionId)) {
			this.deactivateReactionInState(reactionId);
		} else {
			this.activateReactionInState(reactionId);
		}

		this.props.sendUserCommand(this.props.message.id, 'reactToMessage', { id: this.props.message.id, channel: this.props.message.channel, reactionId: reactionId });
	}

	activateReactionInState(reactionId) {
		let reaction = this.state.counters[reactionId] ? this.state.counters[reactionId] + 1 : 1;
		let own = !this.state.own.includes(reactionId) ? [ ...this.state.own, reactionId ] : [ ...this.state.own ];

		this.setState({ counters: { ...this.state.counters, [reactionId]: reaction >= 0 ? reaction : 0 }, own: own });
	}

	deactivateReactionInState(reactionId) {
		let reaction = this.state.counters[reactionId] ? this.state.counters[reactionId] - 1 : 0;
		let own = this.state.own.filter( ownReactionId => ownReactionId !== reactionId );

		this.setState({ counters: { ...this.state.counters, [reactionId]: reaction >= 0 ? reaction : 0 }, own: own });
	}

	getClassName(reaction) {
		let classes = ['wcFunctional', 'wcReactionButton', 'wcReactionButton' + reaction.id];

		if (this.isActive(reaction.id)) {
			classes.push('wcReactionButtonActive');
		}

		if (reaction.class) {
			classes.push(reaction.class);
		}

		return classes.join(' ');
	}

	isActive(reactionId) {
		return this.state.own.includes(reactionId);
	}

	isAnyReactionActive() {
		const configuration = this.props.configuration.interface.message.reactions;

		return this.state.own.filter( ownReactionId => configuration.list.map( reaction => reaction.id ).includes(ownReactionId) ).length > 0
	}

	renderCounters() {
		const configuration = this.props.configuration.interface.message.reactions;
		const activeCounters = configuration.list.filter( reaction => this.state.counters[reaction.id] && this.state.counters[reaction.id] > 0 );

		if (activeCounters.length === 0) {
			return null;
		}

		return <div className="wcReactionsCounters">
			{ activeCounters.map( (reaction, index) =>
				<span
					key={ index }
					className={ 'wcReactionCounter wcReactionCounter' + reaction.id + (reaction.class ? ' ' + reaction.class : '') }
					onClick={ this.loadReactionsLog }
				>
					<span>{ this.state.counters[reaction.id] }</span>
					{ reaction.iconSm ? <img src={ reaction.iconSm } alt={ reaction.action } /> : reaction.counter }
				</span>
			) }
		</div>;
	}

	loadReactionsLog() {
		this.props.sendUserCommand('reactionsLog', 'reactionsLog', { messageId: this.props.message.id, channel: this.props.message.channel });
		this.setState({ reactionsModalOpen: true });
	}

	render() {
		const compactMode = this.props.configuration.interface.message.compact;
		const configuration = this.props.configuration.interface.message.reactions;
		if (!configuration.enabled || configuration.list.length === 0) {
			return null;
		}
		const first = configuration.list[0];
		const iconVisible = (reaction) => reaction.icon && true;
		const textVisible = true;

		return (
			<React.Fragment>
				{ this.renderCounters() }

				{ this.isAnyReactionActive() ? (
					<div className="wcReactionsButtons wcReactionsDeactivate">
						<div className="wcReactionsButtonsList">
							{ configuration.list.filter( reaction => this.state.own.includes(reaction.id) ).filter( (reaction, index) => index === 0 ).map( (reaction, index) =>
								<a key={ index } href="#" onClick={e => this.toggleReaction(e, reaction.id )} className={ this.getClassName( reaction )}>
									{ iconVisible(reaction) && <img src={ reaction.icon } alt={ reaction.action } /> } { textVisible && <span>{ reaction.active }</span> }
								</a>
							)}
						</div>

						{ compactMode && <Time timeUTC={ this.props.message.timeUTC } dateVisible={ false } /> }
					</div>
				) : (
					<div className="wcReactionsButtons wcReactionsActivate">
						<div className="wcReactionsButtonsList">
							{ configuration.list.map( (reaction, index) =>
								<a key={ index } href="#" onClick={e => this.toggleReaction(e, reaction.id )} className={ this.getClassName( reaction )}>
									{ iconVisible(reaction) && <img src={ reaction.icon } alt={ reaction.action } /> } { textVisible && <span>{ reaction.action }</span> }
								</a>
							) }
						</div>

						{ compactMode && <Time timeUTC={ this.props.message.timeUTC } dateVisible={ false } /> }
					</div>
				)}

				<Modal
					open={ this.state.reactionsModalOpen }
					onClose={ e => this.setState({ reactionsModalOpen: false }) }
					title={ this.props.i18n.reactions }
					closable={ true }
					closeOnDocumentClick={ true }
				>
					{ this.props.reactionsLog && this.props.reactionsLog.inProgress && <Spinner /> }

					{ this.props.reactionsLog && this.props.reactionsLog.result && this.props.reactionsLog.result.log.length > 0 &&
						<div className="wcHeight15 wcPadding1 wcMessageReactions">
							<Scrollbar noScrollX={ true }>
								{ this.props.reactionsLog.result.log.map( (entry, index) =>
									<div key={ index } className="wcFormRow wcFlex wcFlexAlignCenter">
										<img src={ entry.user.avatarUrl } alt={ entry.user.name } className={ `wcUserAvatar wcChannelAvatar wcMarginRight1` } />
										<span className="wcUserName wcMarginRight3">{ entry.user.name }</span>
										{ entry.reaction && <img src={ entry.reaction.iconSm } alt={ entry.reaction.action } className={ `wcReaction wcHeight1 ${entry.reaction.class} wcMarginRight1` } /> }
										{ entry.reaction && <span className={ `wcReactionLabel ${entry.reaction.class} wcMarginRight1` }>{ entry.reaction.action }</span> }
									</div>
								)}
							</Scrollbar>
						</div>
					}
					{ this.props.reactionsLog && this.props.reactionsLog.result && this.props.reactionsLog.result.log.length === 0 &&
						<span className="wcNoReactions">{ this.props.i18n.noReactions }</span>
					}
				</Modal>
			</React.Fragment>
		);
	}

}

Reactions.propTypes = {
	configuration: PropTypes.object.isRequired,
	message: PropTypes.object.isRequired
};

export default connect(
	state => ({
		configuration: state.configuration,
		i18nBase: state.configuration.i18n,
		i18n: state.application.i18n,
		reactionsLog: state.commands.sent.reactionsLog
	}),
	{ sendUserCommand, clearUserCommand, confirm, alertError, alertInfo }
)(Reactions);