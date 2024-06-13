import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import HtmlShortcodesRenderer from "utils/html-shortcodes-renderer";
import {Base64} from "js-base64";

class Decorator extends React.Component {

	constructor(props) {
		super(props);

		this.onShortcodeRender = this.onShortcodeRender.bind(this);
		this.htmlShortcodesRenderer = new HtmlShortcodesRenderer(props.configuration, {
			onShortcodeRender: this.onShortcodeRender
		});
	}

	onShortcodeRender(name, params, index, full) {
		switch (name) {
			case 'link':
				return <a key={ index } href={ params.src } target={ params.target } className={ params.className } title={ params.title } rel="noopener noreferrer nofollow" data-org={ Base64.encode(full) }>{ params.name ? params.name : params.src }</a>;
			case 'img':
				return <img key={ index } src={ params.src } className={ params.className } alt={ params.alt } />
			case 'span':
				return <span key={ index } className={ params.className }>{ params.content }</span>
		}

		return null;
	}

	render() {
		return(
			<React.Fragment>
				{this.htmlShortcodesRenderer.toHtml(this.props.children)}
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