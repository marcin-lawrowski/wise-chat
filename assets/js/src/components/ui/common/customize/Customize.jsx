import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import CustomizeArea from "./CustomizeArea";

class Customize extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			visible: false
		}

		this.togglePanel = this.togglePanel.bind(this);
	}

	togglePanel(e) {
		e.preventDefault();

		this.setState({ visible: !this.state.visible });
	}

	render() {
		if (!this.props.user.settings.allowCustomize) {
			return null;
		}

		return(
			<div className={ 'wcCustomizations' + (!this.props.visible ? ' wcInvisible' : '') } style={ { backgroundColor: this.props.configuration.defaultBackgroundColor } }>
				<a href="#" className="wcCustomizeButton wcFunctional" onClick={ e => this.togglePanel(e) }>{ this.props.i18nBase.customize }</a>

				<CustomizeArea visible={ this.state.visible } onSave={ () => this.setState({ visible: false }) } />
			</div>
		)

	}

}

Customize.defaultProps = {
	visible: true
};

Customize.propTypes = {
	configuration: PropTypes.object.isRequired,
	visible: PropTypes.bool.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		user: state.application.user,
		i18nBase: state.configuration.i18n
	})
)(Customize);