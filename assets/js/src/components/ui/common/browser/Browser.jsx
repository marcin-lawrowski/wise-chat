import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import FullChannelsMode from "./FullChannelsMode";

class Browser extends React.Component {
	render() {
		let modeClass = 'wcBrowserFullChannels';

		return(
			<div className={ `wcBrowser ${modeClass}` + (!this.props.visible ? ' wcInvisible' : '') } style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				<FullChannelsMode infoWindowPosition={ this.props.infoWindowPosition } keepInside={ this.props.keepInside } />
			</div>
		)
	}
}

Browser.defaultProps = {
	visible: true,
	infoWindowPosition: "left center"
};

Browser.propTypes = {
	configuration: PropTypes.object.isRequired,
	visible: PropTypes.bool.isRequired,
	infoWindowPosition: PropTypes.string.isRequired,
	keepInside: PropTypes.string
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(Browser);