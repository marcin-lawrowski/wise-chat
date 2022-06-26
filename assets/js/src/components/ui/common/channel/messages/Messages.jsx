import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { Scrollbar } from "react-scrollbars-custom";
import Message from "./message/Message";
import { notify } from "actions/ui";
import { logInfo } from "actions/log";

class Messages extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			autoScrollDisabled: this.props.configuration.messagesOrder !== 'ascending',
			autoScrollTemporaryDisabled: false
		}

		this.notificationsEnabled = false;
		this.scrollRef = React.createRef();

		this.handleScrollUpdate = this.handleScrollUpdate.bind(this);
		setTimeout(function() { this.notificationsEnabled = true; }.bind(this), 2000);
		this.handleStopScroll = this.handleStopScroll.bind(this);
	}

	componentDidUpdate(prevProps) {
		const messagesChange = this.props.messages !== prevProps.messages && this.props.messages.length > 0;

		if (messagesChange && this.notificationsEnabled) {
			const prevIds = Array.isArray(prevProps.messages) ? prevProps.messages.map( message => message.id ) : [];
			const diff = this.props.messages.filter( message => !prevIds.includes(message.id) );
			if (diff.length > 0) {
				let wasNotified = false;

				// scan for current user's name:
				let regexp = new RegExp("@" + this.props.user.name, "g");
				if (diff.filter( message => message.text.match(regexp)).length > 0) {
					this.props.notify('mentioned');
					wasNotified = true;
				}

				if (!wasNotified) {
					this.props.notify('newMessage');
				}
			}
		}
	}

	handleStopScroll(scrollValues, prevScrollValues) {
		let diff = scrollValues.scrollHeight - (scrollValues.clientHeight + scrollValues.scrollTop);
		let result = diff > 0;

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
		let messages = [];

		if (this.props.messages) {
			messages = this.props.configuration.messagesOrder !== 'ascending' ? [].concat(this.props.messages).reverse() : this.props.messages;
		}

		return(
			<Scrollbar ref={ this.scrollRef } className="wcMessages" onUpdate={ this.handleScrollUpdate }
				onScrollStop={ this.handleStopScroll } noScrollX={ true }>
				{ messages.map( (message, index) =>
					<Message key={ message.id } channel={ this.props.channel } message={ message } />
				)}
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
		messages: state.messages.received[ownProps.channel.id]
	}),
	{ notify, logInfo }
)(Messages);