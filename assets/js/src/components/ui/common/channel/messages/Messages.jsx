import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { Scrollbar } from "react-scrollbars-custom";
import Message from "./message/Message";
import { notify } from "actions/ui";
import { logInfo } from "actions/log";
import { loadPastMessages } from "actions/messages";
import Loader from "ui/common/Loader";

class Messages extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			autoScrollDisabled: this.props.configuration.messagesOrder !== 'ascending',
			autoScrollTemporaryDisabled: false,
			noPastMessages: false,
			messages: this.messagesToState(props.messages)
		}

		this.notificationsEnabled = false;
		this.scrollRef = React.createRef();

		this.handleScrollUpdate = this.handleScrollUpdate.bind(this);
		setTimeout(function() { this.notificationsEnabled = true; }.bind(this), 2000);
		this.handleStopScroll = this.handleStopScroll.bind(this);
	}

	componentDidUpdate(prevProps) {
		const messagesChange = this.props.messages !== prevProps.messages && this.props.messages.length > 0;
		const messagesPastLoaded = this.props.messagesPast !== prevProps.messagesPast && this.props.messagesPast.success === true && this.props.messagesPast.result.length > 0;
		const messagesPastLoadedEmpty = this.props.messagesPast !== prevProps.messagesPast && this.props.messagesPast.success === true && this.props.messagesPast.result.length === 0;

		if (messagesChange) {
			const mode = this.props.configuration.notifications.newMessage.mode;
			const prevIds = Array.isArray(prevProps.messages) ? prevProps.messages.map( message => message.id ) : [];
			const diff = this.props.messages.filter(
				message => !prevIds.includes(message.id) && !message.locked && !message.own &&
					(mode === '' || (mode === 'direct' && this.props.channel.type === 'direct') || (mode === 'public' && this.props.channel.type === 'public'))
			);
			if (diff.length > 0 && this.notificationsEnabled) {
				let wasNotified = false;

				let regexp = new RegExp("@" + this.props.user.name, "g");
				if (diff.filter(message => message.text.match(regexp)).length > 0) {
					this.props.notify('mentioned');
					wasNotified = true;
				}

				if (!wasNotified) {
					this.props.notify('newMessage');
				}
			}

			this.setState( state => ({ messages: this.messagesToState(this.props.messages) }) );
		}

		if (messagesPastLoaded) {
			if (this.props.configuration.messagesOrder === 'ascending') {
				this.scrollRef.current.scrollTo(0, 200);
			}
		}
		if (messagesPastLoadedEmpty) {
			this.setState({ noPastMessages: true });
		}
	}

	messagesToState(messages) {
		return messages
			? (this.props.configuration.messagesOrder !== 'ascending' ? [].concat(messages).reverse() : messages)
			: [];
	}

	handleStopScroll(scrollValues, prevScrollValues) {
		let diff = scrollValues.scrollHeight - (scrollValues.clientHeight + scrollValues.scrollTop);
		let result = diff > 0;

		if (!this.state.noPastMessages && this.state.messages.length > 0) {
			if (scrollValues.scrollTop === 0 && this.props.configuration.messagesOrder === 'ascending') {
				this.props.loadPastMessages(this.props.channel.id, this.state.messages[0].id);
			}
			if (this.props.configuration.messagesOrder !== 'ascending' && scrollValues.scrollHeight - scrollValues.scrollTop === scrollValues.clientHeight) {
				this.props.loadPastMessages(this.props.channel.id, this.state.messages[this.state.messages.length - 1].id);
			}
		}

		if (result !== this.state.autoScrollTemporaryDisabled) {
			this.setState({ autoScrollTemporaryDisabled: result });
		}
	}

	handleScrollUpdate(scrollValues, prevScrollValues) {
		if (this.state.autoScrollDisabled || this.state.autoScrollTemporaryDisabled) {
			return;
		}

		if (prevScrollValues.scrollHeight !== scrollValues.scrollHeight) {
			this.scrollRef.current.scrollToBottom();
		}

		if (prevScrollValues.clientHeight !== scrollValues.clientHeight) {
			this.scrollRef.current.scrollToBottom();
		}
	}

	render() {
		return(
			<Scrollbar ref={ this.scrollRef } className="wcMessages" onUpdate={ this.handleScrollUpdate }
				onScrollStop={ this.handleStopScroll } noScrollX={ true }>
				{ this.props.configuration.messagesOrder === 'ascending' && this.props.messagesPast && this.props.messagesPast.inProgress &&
					<Loader message={ this.props.configuration.i18n.loading } center={ true } marginTop={ 10 } marginBottom={ 10 } />
				}

				{ this.state.messages.map( (message, index) =>
					<Message key={ message.id } channel={ this.props.channel } message={ message } />
				)}

				{ this.props.configuration.messagesOrder !== 'ascending' && this.props.messagesPast && this.props.messagesPast.inProgress &&
					<Loader message={ this.props.configuration.i18n.loading } center={ true } marginTop={ 10 } marginBottom={ 10 } />
				}
			</Scrollbar>
		)
	}

}

Messages.propTypes = {
	configuration: PropTypes.object.isRequired,
	channel: PropTypes.object.isRequired,
	messages: PropTypes.array
};

export default connect(
	(state, ownProps) => ({
		configuration: state.configuration,
		user: state.application.user,
		focusedChannel: state.ui.focusedChannel,
		messages: state.messages.received[ownProps.channel.id],
		messagesPast: state.messages.receivedPast[ownProps.channel.id]
	}),
	{ notify, logInfo, loadPastMessages }
)(Messages);