import React from "react";
import { connect } from "react-redux";
import Popup from 'reactjs-popup';
import { clearConfirm } from "actions/ui";
import Modal from "../modal/Modal";

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
		this.close();
		if (this.props.confirms && this.props.confirms.cancelCallback) {
			this.props.confirms.cancelCallback();
		}
	}

	handleConfirm() {
		this.close();
		if (this.props.confirms.callback) {
			this.props.confirms.callback();
		}
	}

	handleCustomButton(event, button) {
		event.preventDefault();

		this.close();
		if (button.callback) {
			button.callback();
		}
	}

	render() {
		if (!this.props.confirms) {
			return null;
		}

		let footer = [];
		if (!this.props.confirms.configuration?.buttonNo?.hidden) {
			footer.push(<button key="no" className="wcButton wcNoButton" onClick={this.handleCancel}>{this.props.confirms.configuration?.buttonNo?.text ?? this.props.i18n.no}</button>);
		}
		if (!this.props.confirms.configuration?.buttonYes?.hidden) {
			footer.push(<button key="yes" className="wcButton wcYesButton" onClick={ this.handleConfirm }>{ this.props.confirms.configuration?.buttonYes?.text ?? this.props.i18n.yes }</button>);
		}
		if (this.props.confirms.buttons) {
			this.props.confirms.buttons.map( (button, index) => {
				if (button.type === 'link') {
					footer.push(<a key={ index } href="#" className={ "wcButton " + (button.className ?? '') } onClick={ e => this.handleCustomButton(e, button) }>{ button.text }</a>);
				} else {
					footer.push(<button key={ index } className={ "wcButton " + (button.className ?? '') } onClick={ e => this.handleCustomButton(e, button) }>{ button.text }</button>);
				}
			});
		}

		return <Modal
			className={ this.props.confirms.configuration.className }
			open={ this.state.popupOpen }
			closeOnDocumentClick={ true }
			onClose={ this.handleCancel }
			title={ this.props.confirms.configuration.title ? this.props.confirms.configuration.title : this.props.i18n.confirmation }
			footer={ footer }
			footerCloseButtonVisible={ false }
		>
			{ this.props.confirms.text }

			{ this.props.confirms.configuration.sound &&
				<audio loop autoPlay preload="auto">
					<source src={ this.props.confirms.configuration.sound.src } type="audio/ogg" />
				</audio>
			}
		</Modal>
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