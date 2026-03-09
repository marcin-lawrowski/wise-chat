import React from "react";
import { connect } from "react-redux";
import { clearAlerts } from "actions/ui";
import Modal from "../modal/Modal";

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
		return (
			<Modal
				title={ this.state.title }
				open={ this.state.popupOpen }
				onClose={ this.close }
				closeButtonLabel={ this.props.i18n.ok }
				size="sm"
			>
				{ this.state.text }
			</Modal>
		)
	}

}

export default connect(
	(state) => ({
		error: state.ui.alerts.error,
		info: state.ui.alerts.info,
		i18n: state.application.i18n
	}),
	{ clearAlerts }
)(Alerts);