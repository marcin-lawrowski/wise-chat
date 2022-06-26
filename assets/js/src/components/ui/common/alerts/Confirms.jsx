import React from "react";
import { connect } from "react-redux";
import Popup from 'reactjs-popup';
import { clearConfirm } from "actions/ui";

class Confirms extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			popupOpen: false
		};

		this.handleCancel = this.handleCancel.bind(this);
		this.handleConfirm = this.handleConfirm.bind(this);
		this.handleCustomButton = this.handleCustomButton.bind(this);
	}

	componentDidUpdate(prevProps) {
		const posted = this.props.confirms !== prevProps.confirms && this.props.confirms;

		if (posted) {
			this.setState({
				popupOpen: true
			});
		}
	}

	close() {
		this.setState({ popupOpen: false });
		this.props.clearConfirm();
	}

	handleCancel() {
		if (this.props.confirms.cancelCallback) {
			this.props.confirms.cancelCallback();
		}
		this.close();
	}

	handleConfirm() {
		if (this.props.confirms.callback) {
			this.props.confirms.callback();
		}
		this.close();
	}

	handleCustomButton(button) {
		button.callback();
		this.close();
	}

	render() {
		if (!this.props.confirms) {
			return null;
		}

		return(
			<React.Fragment>
				<Popup
					className={ "wcPopup wcAlertPopup wcAlertPopupConfirm " + this.props.configuration.themeClassName }
					open={ this.state.popupOpen }
					modal
					closeOnDocumentClick
					onClose={ this.handleCancel }
				>
					<div className="wcHeader">
						<h5>{ this.props.i18n.confirmation }</h5>
						<a href="#" className="wcClose" title={ this.props.i18n.close } onClick={ e => { e.preventDefault(); this.handleCancel() } } />
					</div>
					<div className="wcBody">
						{ this.props.confirms.text }
					</div>
					<div className="wcFooter">
						<button className="wcNoButton" onClick={ this.handleCancel }>{ this.props.i18n.no }</button>
						<button className="wcYesButton" onClick={ this.handleConfirm }>{ this.props.i18n.yes }</button>
						{ this.props.confirms.buttons && this.props.confirms.buttons.map( (button, index) =>
							<button key={ index } className="wcButton" onClick={ e => this.handleCustomButton(button) }>{ button.text }</button>
						)}
					</div>
				</Popup>
			</React.Fragment>
		);
	}

}

export default connect(
	(state) => ({
		confirms: state.ui.confirms,
		i18n: state.application.i18n,
		configuration: state.configuration
	}),
	{ clearConfirm }
)(Confirms);