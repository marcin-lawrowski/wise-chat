import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Messages from "./messages/Messages";
import InputRich from "./input/InputRich";
import AuthChannelPassword from "ui/common/auth/AuthChannelPassword";
import Full from "./components/Full";
import Counter from "ui/common/counter/Counter";

class Channel extends React.Component {

	render() {
		const inputLocation = this.props.configuration.interface.channel.inputLocation === 'top' ? ' wcTopInput' : ' wcBottomInput';
		const auth = this.props.channel.protected === true && this.props.channel.authorized === false;

		return(
			<div
				className={ "wcChannel" + inputLocation + (auth ? ' wcChannelAuth' : '') + (this.props.className ? ' ' + this.props.className : '') }
				data-id={ this.props.channel.id }
				data-name={ this.props.channel.name }
				style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }
			>
				{ auth ? (
					<div className="wcAuthContainer">
						<div className="wcAuth">
							<AuthChannelPassword channel={ this.props.channel } />
						</div>
					</div>
				) : (
					<React.Fragment>
						{ this.props.channel.full ? (
							<Full />
						) : (
							<React.Fragment>
								<Messages channel={ this.props.channel } />
								{ !this.props.configuration.interface.browser.enabled && <Counter /> }
								<InputRich channel={ this.props.channel } />
							</React.Fragment>
						)}
					</React.Fragment>
				)}
			</div>
		)
	}

}

Channel.propTypes = {
	configuration: PropTypes.object.isRequired,
	channel: PropTypes.object.isRequired,
	className: PropTypes.string
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(Channel);