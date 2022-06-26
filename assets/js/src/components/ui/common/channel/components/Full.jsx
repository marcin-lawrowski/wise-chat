import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";

class Full extends React.Component {

	render() {
		return(
			<div className="wcChannelCoverContainer">
				<div className="wcChannelCover">
					<div className="wcChatFullMessage wcErrorBox">{ this.props.i18n.chatFull }</div>
				</div>
			</div>
		)
	}

}

Full.defaultProps = {
	titleVisible: true
};

Full.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18n: state.application.i18n
	})
)(Full);