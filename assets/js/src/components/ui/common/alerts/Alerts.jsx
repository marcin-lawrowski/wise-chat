import React from "react";
import { connect } from "react-redux";
import Popup from 'reactjs-popup';
import { clearAlerts } from "actions/ui";

class Alerts extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			type: "Info",
			popupOpen: false,
			title: '',
			text: ''
		};

		this.close = this.close.bind(this);
	}

	componentDidUpdate(prevProps) {
		const postedError = this.props.error !== prevProps.error && this.props.error;
		const postedInfo = this.props.info !== prevProps.info && this.props.info;

		if (postedError) {
			this.setState({ type: 'Error', popupOpen: true, title: this.props.i18n.error, text: this.props.error });
			this.props.clearAlerts();
		}

		if (postedInfo) {
			this.setState({ type: 'Info', popupOpen: true, title: this.props.i18n.information, text: this.props.info });
			this.props.clearAlerts();
		}
	}

	close() {
		this.setState({ popupOpen: false, title: '', text: '' })
	}

	render() {
		return(
			<React.Fragment>
				<Popup
					className={ "wcPopup wcAlertPopup wcAlertPopup" + this.state.type + ' ' + this.props.configuration.themeClassName }
					open={ this.state.popupOpen }
					modal
					closeOnDocumentClick
					onClose={ this.close }
				>
					<div className="wcHeader">
						<h5>{ this.state.title }</h5>
						<a href="#" className="wcClose" title={ this.props.i18n.close } onClick={ e => { e.preventDefault(); this.close() } } />
					</div>
					<div className="wcBody">
						{ this.state.text }
					</div>
					<div className="wcFooter">
						<button className="wcCloseButton" onClick={ this.close }>{ this.props.i18n.ok }</button>
					</div>
				</Popup>
			</React.Fragment>
		);
	}

}

export default connect(
	(state) => ({
		error: state.ui.alerts.error,
		info: state.ui.alerts.info,
		i18n: state.application.i18n,
		configuration: state.configuration
	}),
	{ clearAlerts }
)(Alerts);