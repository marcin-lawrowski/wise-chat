import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Decorator from "./Decorator";
import Sender from "./Sender";
import Time from "./Time";
import Avatar from "./Avatar";

class Quoted extends React.Component {

	render() {
		return(
			<div className="wcQuote">
				<div className="wcQuoteHead">
					<Avatar message={ this.props.message } />
					<Sender message={ this.props.message } />
					<Time timeUTC={ this.props.message.timeUTC } />
				</div>
				<div className="wcQuoteContent">
					<Decorator>
						{ this.props.message.text }
					</Decorator>
				</div>
			</div>
		)
	}

}

Quoted.propTypes = {
	configuration: PropTypes.object.isRequired,
	message: PropTypes.object.isRequired
};

export default connect(
	state => ({
		configuration: state.configuration
	})
)(Quoted);