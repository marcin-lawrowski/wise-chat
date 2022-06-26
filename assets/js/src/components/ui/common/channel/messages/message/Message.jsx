import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Time from "./Time";
import Sender from "./Sender";
import $ from "jquery";
import Actions from "./Actions";
import Decorator from "./Decorator";
import Avatar from "./Avatar";

class Message extends React.Component {

	get ACTIONS_AUTO_THRESHOLD() {
		return 500;
	}

	constructor(props) {
		super(props);

		this.state = {
			actionsVisible: false,
			hover: false
		}

		this.handleEnter = this.handleEnter.bind(this);
		this.handleLeave = this.handleLeave.bind(this);
		this.handleClick = this.handleClick.bind(this);
		this.handleDocumentClick = this.handleDocumentClick.bind(this);
		this.messageRef = React.createRef();
		this.contentRef = React.createRef();
	}

	componentDidMount() {
		$(document).on('click', this.handleDocumentClick);
	}

	componentWillUnmount() {
		$(document).off('click', this.handleDocumentClick);
	}

	handleEnter() {
		const elementWidth = $(this.messageRef.current).width();

		if (elementWidth > this.ACTIONS_AUTO_THRESHOLD) {
			this.setState({actionsVisible: true});
		}
		this.setState({hover: true});
	}

	handleLeave() {
		const elementWidth = $(this.messageRef.current).width();

		if (elementWidth > this.ACTIONS_AUTO_THRESHOLD) {
			this.setState({actionsVisible: false});
		}
		this.setState({hover: false});
	}

	handleClick(e) {
		const elementWidth = $(this.messageRef.current).width();

		if (elementWidth <= this.ACTIONS_AUTO_THRESHOLD) {
			if (this.state.actionsVisible) {
				setTimeout(function () {
					this.setState({actionsVisible: false});
				}.bind(this), 200);
			} else {
				this.setState({actionsVisible: true});
			}
		}
	}

	handleDocumentClick(e) {
		if ($(e.target).closest(this.messageRef.current).length === 0 && this.state.actionsVisible) {
			this.setState({actionsVisible: false});
		}
	}

	render() {
		const classes = ['wcMessage'];
		if (this.state.hover) {
			classes.push('wcHover');
		}
		if (this.props.message.sender.current) {
			classes.push('wcCurrentUser');
		}
		if (this.props.message.sender.source === 'w') {
			classes.push('wcWpUser');
		}
		if (this.props.message.cssClasses) {
			classes.push(this.props.message.cssClasses);
		}

		return(
			<div ref={ this.messageRef } className={ classes.join(' ') } onClick={ this.handleClick } onMouseEnter={ this.handleEnter } onMouseLeave={ this.handleLeave }>
				<Actions message={ this.props.message } visible={ this.state.actionsVisible }/>

				<div className="wcRowHead">
					<Sender message={ this.props.message } />
					<Time timeUTC={ this.props.message.timeUTC } />
				</div>

				<div className="wcRowBody">
					<Avatar message={ this.props.message } />

					<div className="wcContent">
						<div ref={ this.contentRef } className="wcInternalContent" style={{ color: this.props.message.color }}>
							<Decorator>
								{ this.props.message.text }
							</Decorator>
						</div>
					</div>
				</div>
			</div>
		)
	}

}

Message.propTypes = {
	configuration: PropTypes.object.isRequired,
	channel: PropTypes.object.isRequired,
	message: PropTypes.object.isRequired,
	i18n: PropTypes.object.isRequired,
	i18nBase: PropTypes.object
};

export default connect(
	(state, ownProps) => ({
		configuration: state.configuration,
		userRights: state.application.user.rights,
		i18n: state.application.i18n,
		i18nBase: state.configuration.i18n
	})
)(Message);