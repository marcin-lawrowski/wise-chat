import React from "react";
import PropTypes from 'prop-types';
import {connect} from "react-redux";

class Avatar extends React.Component {

	constructor(props) {
		super(props);

		this.handleError = this.handleError.bind(this);
	}

	handleError(e) {
		e.target.src = this.props.configuration.baseDir + '/gfx/icons/user.png';
	}

	render() {
		const sender = this.props.message.sender;
		if (!sender.avatarUrl) {
			return null;
		}

		return(
			<img className="wcAvatar wcFunctional" onError={ this.handleError } src={ sender.avatarUrl } alt={ sender.name } />
		)
	}

}

Avatar.propTypes = {
	message: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(Avatar);