import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Popup from "reactjs-popup";

class Logger extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			popupOpen: false
		};

		this.close = this.close.bind(this);
		this.show = this.show.bind(this);
	}

	close() {
		this.setState({ popupOpen: false })
	}

	show() {
		this.setState({ popupOpen: true })
	}

	render() {
		return(
			<div className="wcLogger" style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				<a href="#" onClick={ e => { e.preventDefault(); this.show(); } } className="wcDebugLink">[Debug Chat ({ this.props.entries.length })]</a>

				<Popup
					className={ "wcPopup wcDebugPopup " + this.props.configuration.themeClassName }
					open={ this.state.popupOpen }
					modal
					closeOnDocumentClick
					onClose={ this.close }
					contentStyle={ { height: 400 } }
				>
					<div className="wcDebugPopupBody">
						<span>Debug mode error log. Select and copy the text below.</span>
						<div className="wcLogs">
							{ this.props.entries.length === 0 ? <small>No logs found</small> : ''}

							{ this.props.entries.map( (entry, index) =>
								<React.Fragment key={ index }>
									<small>
										<strong>{ entry.timestamp } [{ entry.level }]</strong> { entry.content } { JSON.stringify(entry.details) }
									</small>
									<br />
								</React.Fragment>
							)}
						</div>
						<div className="wcFooter">
							<button onClick={ e => { e.preventDefault(); this.close(); } }>Close</button>
						</div>
					</div>
				</Popup>
			</div>
		);
	}

}

Logger.propTypes = {
	configuration: PropTypes.object.isRequired,
	entries: PropTypes.array.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		entries: state.log.entries
	})
)(Logger);