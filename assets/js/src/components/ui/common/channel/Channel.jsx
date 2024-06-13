import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Messages from "./messages/Messages";
import InputRich from "./input/InputRich";
import AuthChannelPassword from "ui/common/auth/AuthChannelPassword";
import Full from "./components/Full";
import Counter from "ui/common/counter/Counter";
import $ from "jquery";

class Channel extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			view: 'Compound'
		}

		this.element = React.createRef();
		this.switchView = this.switchView.bind(this);
		this.getClasses = this.getClasses.bind(this);
		this.handleResize = this.handleResize.bind(this);
	}

	componentDidMount() {
		window.addEventListener('resize', this.handleResize);
		this.handleResize();
	}

	componentWillUnmount() {
		window.removeEventListener('resize', this.handleResize);
	}

	handleResize() {
		const elementWidth = $(this.element.current).closest('.wcChannel').width();
		let className = '';

		if (elementWidth < 380) {
			className = 'wcChannelSizeXXs';
		} else if (elementWidth < 576) {
			className = 'wcChannelSizeXs';
		} else if (elementWidth < 768) {
			className = 'wcChannelSizeSm';
		} else if (elementWidth < 992) {
			className = 'wcChannelSizeMd';
		} else if (elementWidth < 1200) {
			className = 'wcChannelSizeLg';
		} else {
			className = 'wcChannelSizeXl';
		}

		if (this.state.breakpointUpClassName !== className) {
			this.setState({breakpointUpClassName: className});
		}
	}

	switchView(view) {
		this.setState({ view: view });
	}

	getClasses() {
		let classes = [
			'wcChannel', 'wcChannelView' + this.state.view, this.props.configuration.interface.channel.inputLocation === 'top' ? ' wcTopInput' : ' wcBottomInput'
		];
		if (this.props.className) {
			classes.push(this.props.className);
		}
		if (this.state.breakpointUpClassName) {
			classes.push(this.state.breakpointUpClassName);
		}

		return ' ' + classes.join(' ');
	}

	render() {
		const auth = this.props.channel.protected === true && this.props.channel.authorized === false;

		return(
			<div
				ref={ this.element }
				className={ (auth ? ' wcChannelAuth' : '') + this.getClasses() }
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
								<div className="wcChannelData">
									<Messages channel={ this.props.channel } />
								</div>
								{ (['wcChannelSizeXs', 'wcChannelSizeXXs'].includes(this.state.breakpointUpClassName) || !this.props.configuration.interface.browser.enabled) && <Counter /> }
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
	(state, ownProps) => ({
		i18n: state.application.i18n,
		stream: state.ui.streams.find( stream => stream.channel && stream.channel.id === ownProps.channel.id ),
		uiChannel: state.ui.channels[ownProps.channel.id],
		configuration: state.configuration
	})
)(Channel);