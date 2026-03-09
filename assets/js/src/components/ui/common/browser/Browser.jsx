import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import FullChannelsMode from "./FullChannelsMode";

class Browser extends React.Component {
	render() {
		let modeClass = '';
		if (this.props.configuration.interface.browser.mode === 'full-channels') {
			modeClass = 'wcBrowserFullChannels';
		}
		if (this.props.configuration.interface.browser.mode === 'recent') {
			modeClass = 'wcBrowserRecent';
		}
		if (this.props.configuration.interface.browser.mode === 'recent-with-current') {
			modeClass = 'wcBrowserRecent wcBrowserRecentWithCurrent';
		}

		return(
			<div className={ `wcBrowser ${modeClass}` + (!this.props.visible ? ' wcInvisible' : '') } style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				{ this.props.configuration.interface.browser.mode === 'full-channels' && <FullChannelsMode infoWindowPosition={ this.props.infoWindowPosition } keepInside={ this.props.keepInside } /> }
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