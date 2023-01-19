import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";

class Loading extends React.Component {

	render() {
		return(
			<div className="wcLoadingContainer" style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				{ this.props.titleVisible && this.props.configuration.interface.chat.title &&
					<div className="wcTitle">
						{ this.props.configuration.interface.chat.title }
					</div>
				}
				<div className="wcLoading">
					<div className="wcLoadingMessage">{ this.props.configuration.i18n.loadingChat }</div>
				</div>
			</div>
		)
	}

}

Loading.defaultProps = {
	titleVisible: true
};

Loading.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(Loading);