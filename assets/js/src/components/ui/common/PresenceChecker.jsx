import React from "react";
import PropTypes from 'prop-types';
import { destroy } from "actions/ui";
import $ from 'jquery';
import {connect} from "react-redux";

class PresenceChecker extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			domPresent: true
		};

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
		if ($.contains(document, this.props.rootElement)) {
			if (this.state.domPresent === false) {
				this.setState({ domPresent: true });
			}
		} else {
			if (this.state.domPresent === true) {
				this.setState({ domPresent: false });
				this.props.destroy();
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
	(state) => ({ }), { destroy }
)(PresenceChecker);