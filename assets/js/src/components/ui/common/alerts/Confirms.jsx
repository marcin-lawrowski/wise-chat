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
			this.setState({ popupOpen: true });
			if (this.props.confirms.configuration.timeout) {
				this.closeTimer = setTimeout(() => { this.handleCancel(); }, this.props.confirms.configuration.timeout * 1000);
			}
		}
	}

	componentWillUnmount() {
		this.clearCloseTimer();
	}

	close() {
		this.setState({ popupOpen: false });
		this.props.clearConfirm();
		this.clearCloseTimer();
	}

	clearCloseTimer() {
		if (this.closeTimer) {
			clearTimeout(this.closeTimer);
			delete this.closeTimer;
		}
	}

	handleCancel() {
		if (this.props.confirms && this.props.confirms.cancelCallback) {
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

	handleCustomButton(event, button) {
		event.preventDefault();

		if (button.callback) {
			button.callback();
		}
		this.close();
	}

	render() {
		if (!this.props.confirms) {
			return null;
		}

		return(
			<React.Fragment>
				<Popup
					className={ "wcPopup wcAlertPopup wcAlertPopupConfirm " + this.props.configuration.themeClassName + ' ' + this.props.confirms.configuration.className }
					open={ this.state.popupOpen }
					modal
					closeOnDocumentClick
					onClose={ this.handleCancel }
				>
					<div className="wcHeader">
						<h5>{ this.props.confirms.configuration.title ? this.props.confirms.configuration.title : this.props.i18n.confirmation }</h5>
						<a href="#" className="wcClose" title={ this.props.i18n.close } onClick={ e => { e.preventDefault(); this.handleCancel() } } />
					</div>
					<div className="wcBody">
						{ this.props.confirms.text }

						{ this.props.confirms.configuration.sound &&
							<audio loop autoPlay preload="auto">
								<source src={ this.props.confirms.configuration.sound.src } type="audio/ogg" />
							</audio>
						}
					</div>
					<div className="wcFooter">
						{ !this.props.confirms.configuration?.buttonNo?.hidden &&
							<button className="wcNoButton" onClick={this.handleCancel}>{this.props.confirms.configuration?.buttonNo?.text ?? this.props.i18n.no}</button>
						}
						{ !this.props.confirms.configuration?.buttonYes?.hidden &&
							<button className="wcYesButton" onClick={ this.handleConfirm }>{ this.props.confirms.configuration?.buttonYes?.text ?? this.props.i18n.yes }</button>
						}
						{ this.props.confirms.buttons && this.props.confirms.buttons.map( (button, index) => button.type === 'link' ?
							(
								<a key={ index } href="#" className={ "wcButton " + (button.className ?? '') } onClick={ e => this.handleCustomButton(e, button) }>{ button.text }</a>
							) : (
								<button key={ index } className={ "wcButton " + (button.className ?? '') } onClick={ e => this.handleCustomButton(e, button) }>{ button.text }</button>
							)
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