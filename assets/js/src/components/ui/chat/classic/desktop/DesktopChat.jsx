import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Browser from "ui/common/browser/Browser";
import Logger from "ui/common/logger/Logger";
import TabbedController from "./TabbedController";
import Customize from "ui/common/customize/Customize";
import Toasts from "ui/common/toasts/Toasts";

class DesktopChat extends React.Component {

	render() {
		const browserLocation = this.props.configuration.interface.browser.location;

		return(
			<React.Fragment>
				{ this.props.configuration.interface.chat.title.length > 0 &&
					<div className="wcTitle">
						{ this.props.configuration.interface.chat.title }
					</div>
				}
				<div className={ "wcBody " + (browserLocation === 'left' ? 'wcBrowserAreaLeft' : 'wcBrowserAreaRight')}>
					<div className="wcMessagesArea">
						<TabbedController />
						<Customize />
					</div>
					{this.props.configuration.interface.browser.enabled &&
						<div className="wcBrowserArea">
							<Browser />
						</div>
					}
				</div>
				<Toasts />

				{this.props.configuration.debug &&
					<Logger/>
				}
			</React.Fragment>
		)
	}

}

DesktopChat.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(DesktopChat);