import React from "react";
import { connect } from "react-redux";
import { clearToasts } from "actions/ui";
import {capitalizeFirstLetter} from "utils/string";

class Toasts extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			toasts: []
		};

		this.handleClick = this.handleClick.bind(this);
	}

	componentDidUpdate(prevProps) {
		const toastsPosted = this.props.toasts !== prevProps.toasts && this.props.toasts.length > 0;

		if (toastsPosted) {
			if (this.timeout) {
				clearTimeout(this.timeout);
				this.timeout = null;
			}

			this.setState({ toasts: [...this.state.toasts, ...this.props.toasts] });
			this.props.clearToasts();

			this.timeout = setTimeout(function() {
				this.setState({ toasts: [] });
			}.bind(this), 3000);
		}
	}

	componentWillUnmount() {
		if (this.timeout) {
			clearTimeout(this.timeout);
			this.timeout = null;
		}
	}

	handleClick(clickedIndex) {
		this.setState( { toasts: this.state.toasts.filter( (toast, index) => index !== clickedIndex ) });
	}

	render() {
		if (this.state.toasts.length === 0) {
			return null;
		}

		return(
			<div className="wcToasts" >
				{ this.state.toasts.map( (toast, index) =>
					<div
						key={ index }
						onClick={ e => this.handleClick(index) }
						className={ "wcToast wcToast" + capitalizeFirstLetter(toast.type) }
						style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }
					>
						{ toast.text }
					</div>
				)}
			</div>
		);
	}

}

export default connect(
	(state) => ({
		toasts: state.ui.toasts,
		configuration: state.configuration
	}),
	{ clearToasts }
)(Toasts);