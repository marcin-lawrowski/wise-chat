import React from "react";
import PropTypes from 'prop-types';
import { updateDOMPresence } from "actions/application";
import $ from 'jquery';
import {connect} from "react-redux";

class PresenceChecker extends React.Component {

	constructor(props) {
		super(props);

		this.domCheck = this.domCheck.bind(this);
	}

	componentDidMount() {
		this.interval = setInterval(this.domCheck, 1000);
	}

	componentWillUnmount() {
		if (this.interval) {
			clearInterval(this.interval);
		}
	}

	domCheck() {
		if ($('#' + this.props.configuration.chatId).length > 0) {
			if (this.props.domPresent === false) {
				this.props.updateDOMPresence(true);
			}
		} else {
			if (this.props.domPresent === true) {
				this.props.updateDOMPresence(false);
			}
		}
	}

	render() {
		return null;
	}

}

PresenceChecker.propTypes = {
	rootElement: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		domPresent: state.application.domPresent,
		configuration: state.configuration
	}),
	{ updateDOMPresence }
)(PresenceChecker);