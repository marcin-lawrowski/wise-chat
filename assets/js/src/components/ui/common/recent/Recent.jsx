import React from "react";
import { connect } from "react-redux";
import Popup from "reactjs-popup";
import RecentArea from "./RecentArea";
import PropTypes from "prop-types";

class Recent extends React.Component {

	render() {
		if (!this.props.configuration.interface.recent.enabled || ['recent-with-current', 'recent'].includes(this.props.configuration.interface.browser.mode)) {
			return null;
		}
		const unreadQuantity = this.props.recentChats.filter( recentChat => recentChat.read === false).length;

		return(
			<Popup
				trigger={ open => <a className={ "wcFunctional wcRecentTrigger" + (open ? ' wcOpen' : '') }>{ unreadQuantity ? <span>{ unreadQuantity }</span> : '' }</a> }
				position="left center"
				className={ "wcPopup wcRecentPopup " + this.props.configuration.themeClassName + (this.props.recentChats.length === 0 ? ' wcRecentEmpty' : '' ) }
				on={['click']}
				arrow={ false }
				keepTooltipInside={ this.props.keepInside }
			>
				{ close => <RecentArea onClick={ close } /> }
			</Popup>
		);
	}

}

Recent.defaultProps = {
	keepInside: true
}

Recent.propTypes = {
	configuration: PropTypes.object.isRequired,
	keepInside: PropTypes.oneOfType([
		PropTypes.string,
		PropTypes.bool
	])
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		recentChats: state.application.recentChats
	})
)(Recent);