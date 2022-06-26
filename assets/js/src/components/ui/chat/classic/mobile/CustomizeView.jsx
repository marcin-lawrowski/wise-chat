import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import CustomizeArea from "components/ui/common/customize/CustomizeArea";

class CustomizeView extends React.Component {

	render() {
		return(
			<div className="wcCustomizations">
				<CustomizeArea visible={ this.props.visible } />
			</div>
		)
	}

}

CustomizeView.propTypes = {
	configuration: PropTypes.object.isRequired,
	visible: PropTypes.bool
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(CustomizeView);