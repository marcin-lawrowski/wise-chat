import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { appendToChannelInput } from "actions/ui";

class Sender extends React.Component {

	constructor(props) {
		super(props);

		this.handleMentionClick = this.handleMentionClick.bind(this);
	}

	handleMentionClick(e) {
		e.preventDefault();

		this.props.appendToChannelInput(this.props.message.channel.id, '@' + this.props.message.sender.name + ':');
	}

	render() {
		const mode = this.props.configuration.interface.message.senderMode;
		const sender = this.props.message.sender;
		
		return(
			<React.Fragment>
				{mode === 0 &&
					<span className="wcUser" style={{ color: sender.color }}>
						{ sender.name }
					</span>
				}
				{mode === 1 && sender.profileUrl &&
					<a
						href={ sender.profileUrl }
						className="wcUser"
						style={{ color: sender.color }}
						title={ sender.name }
						target='_blank'
						rel='noopener noreferrer nofollow'
					>
						{ sender.name }
					</a>
				}
				{mode === 1 && !sender.profileUrl &&
					<span className="wcUser" style={{ color: sender.color }}>
						{ sender.name }
					</span>
				}
				{mode === 2 &&
					<a
						href="#"
						className="wcUser"
						style={{ color: sender.color }}
						title={ this.props.i18n.insertIntoMessage + ': @' + sender.name + ':' }
						rel='noopener noreferrer nofollow'
						onClick={ this.handleMentionClick }
					>
						{ sender.name }
					</a>
				}
			</React.Fragment>
		)
	}

}

Sender.propTypes = {
	configuration: PropTypes.object.isRequired,
	message: PropTypes.object.isRequired
};

export default connect(
	state => ({
		configuration: state.configuration,
		i18n: state.configuration.i18n,
	}),
	{ appendToChannelInput }
)(Sender);