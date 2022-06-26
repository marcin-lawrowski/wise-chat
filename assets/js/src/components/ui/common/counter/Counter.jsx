import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";

class Counter extends React.Component {

	render() {
		if (!this.props.configuration.interface.counter.onlineUsers) {
			return null;
		}

		return(
			<div className="wcCounter">
				{ this.props.i18n.onlineUsers }: { this.props.onlineUsersCounter }
			</div>
		)
	}

}

Counter.propTypes = {
	configuration: PropTypes.object.isRequired,
	onlineUsersCounter: PropTypes.number.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		onlineUsersCounter: state.application.onlineUsersCounter,
		i18nBase: state.configuration.i18n,
		i18n: state.application.i18n
	})
)(Counter);