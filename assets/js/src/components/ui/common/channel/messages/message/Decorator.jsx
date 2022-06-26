import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import HtmlRenderer from "utils/html-renderer";

class Decorator extends React.Component {

	constructor(props) {
		super(props);

		this.htmlRenderer = new HtmlRenderer(props.configuration);
	}

	render() {
		return(
			<React.Fragment>
				{this.htmlRenderer.toHtml(this.props.children)}
			</React.Fragment>
		)
	}
}

Decorator.propTypes = {
	configuration: PropTypes.object.isRequired
};

export default connect(
	state => ({
		configuration: state.configuration
	})
)(Decorator);