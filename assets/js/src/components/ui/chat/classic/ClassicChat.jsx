import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import DesktopChat from "./desktop/DesktopChat";
import MobileChat from "./mobile/MobileChat";
import $ from "jquery";
import Auth from "ui/common/auth/Auth";
import Loading from "ui/common/loading/Loading";
import Logger from "ui/common/logger/Logger";

class ClassicChat extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			breakpointUpClassName: ''
		};
		this.element = React.createRef();
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
		const elementWidth = $(this.element.current).closest('.wcContainer').width();
		let className = '';

		if (elementWidth < 380) {
			className = 'wcSizeXXs';
		} else if (elementWidth < 576) {
			className = 'wcSizeXs';
		} else if (elementWidth < 768) {
			className = 'wcSizeSm';
		} else if (elementWidth < 992) {
			className = 'wcSizeMd';
		} else if (elementWidth < 1200) {
			className = 'wcSizeLg';
		} else {
			className = 'wcSizeXl';
		}

		if (this.state.breakpointUpClassName !== className) {
			this.setState({breakpointUpClassName: className});
		}
	}

	render() {
		const isMobile = ['wcSizeXXs', 'wcSizeXs'].includes(this.state.breakpointUpClassName);
		const loading = !this.props.user && !this.props.auth;
		if (loading) {
			return <div className="wcClassic" ref={ this.element }>
				<Loading />
				{this.props.configuration.debug &&
					<Logger/>
				}
			</div>;
		}

		return(
			<div className={ `wcClassic ${ this.state.breakpointUpClassName } ${isMobile ? 'wcMobile' : 'wcDesktop' }` } ref={ this.element }>
				{ this.props.user &&
					<React.Fragment>
						{isMobile ? <MobileChat/> : <DesktopChat/>}
					</React.Fragment>
				}
				{ this.props.user === null && this.props.auth &&
					<Auth />
				}
			</div>
		)
	}

}

ClassicChat.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		user: state.application.user,
		auth: state.application.auth
	})
)(ClassicChat);