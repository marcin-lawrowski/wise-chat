import React from "react";
import PropTypes from "prop-types";
import $ from "jquery";
import {connect} from "react-redux";
import HtmlRenderer from "utils/html-renderer";
import { renderToString } from 'react-dom/server';
import { logError } from "actions/log";

class HtmlInput extends React.Component {

	constructor(props) {
		super(props);

		this.editableRef = React.createRef();
		this.htmlRenderer = new HtmlRenderer(props.configuration);
		this.handleChange = this.handleChange.bind(this);
		this.handleClick = this.handleClick.bind(this);
		this.handleKeyDown = this.handleKeyDown.bind(this);
		this.handlePaste = this.handlePaste.bind(this);
	}

	componentDidUpdate(prevProps) {
		const inputRequestChange = this.props.inputRequest !== prevProps.inputRequest;
		const resetRequestChange = this.props.resetRequest !== prevProps.resetRequest && this.props.resetRequest === true;

		if (inputRequestChange && this.props.inputRequest) {
			if (this.isMaximumOfCharactersReached()) {
				return;
			}

			// convert input request into HTML string:
			let node = $.parseHTML(this.convertMessageToHTML(this.props.inputRequest))[0];

			if (this.range) {
				// append the HTML at the previous caret position:
				this.range.deleteContents();
				this.range.insertNode(node);
				this.range.collapse(false);
				this.selection.removeAllRanges();
				this.selection.addRange(this.range);
			} else {
				this.props.logError('Range is not available, fallback to jQuery append');
				$(this.editableRef.current).append(node);
			}

			this.triggerChange();
		}

		if (resetRequestChange) {
			$(this.editableRef.current).html('');
		}
	}

	convertMessageToHTML(message) {
		return renderToString(this.htmlRenderer.toHtml(message))
	}

	convertHTMLToMessage(htmlSource) {
		const html = $('<div>' + htmlSource + '</div>');

		// restore shortcodes:
		html.find('[data-org]').each( (index, element) => {
			$(element).replaceWith(atob($(element).attr('data-org')));
		});

		// replace all children (including texts) with new line and their text content:
		const finalHtml = $('<div>' + html.html() + '</div>');
		finalHtml.contents().each( (index, element) => {
			if ($(element).prop("tagName") !== 'BR') {
				$(element).replaceWith($(element).text() + '\n');
			}
		});

		return finalHtml.text();
	}

	getCharactersCount(htmlSource) {
		const html = $('<div>' + htmlSource + '</div>');

		// reduce shortcodes to single character:
		html.find('[data-org]').each( (index, element) => {
			$(element).replaceWith('0');
		});

		// replace all children (including texts) with their text content:
		const finalHtml = $('<div>' + html.html() + '</div>');
		finalHtml.contents().each( (index, element) => {
			$(element).replaceWith($(element).text());
		});

		return finalHtml.text().length;
	}

	isMaximumOfCharactersReached() {
		return this.getCharactersCount($(this.editableRef.current).html()) >= this.props.configuration.interface.input.maxLength;
	}

	handleClick() {
		this.storeSelectedRange();
	}

	handleChange() {
		this.triggerChange();
		this.storeSelectedRange();
	}

	handleKeyDown(e) {
		const easyMode = this.props.configuration.interface.input.multilineEasy;
		const stayMultiline = e.shiftKey && !easyMode || !e.shiftKey && easyMode;

		// handle characters limit:
		if (this.isPrintableKey(e.nativeEvent) && this.isMaximumOfCharactersReached()) {
			e.nativeEvent.preventDefault();
		}

		// if multiline mode is not enabled send the message:
		if (e.keyCode === 13 && !stayMultiline) {
			this.props.onSendingRequest && this.props.onSendingRequest();

			// prevents moving the caret to the new line:
			e.nativeEvent.preventDefault();
		}
	}

	isPrintableKey(event) {
		return event && event.key && event.key.length === 1;
	}

	/**
	 * Converts pasted content into plain text to prevent HTML tags.
	 *
	 * @param {SyntheticEvent } e
	 */
	handlePaste(e) {
		e.preventDefault();

		e = e.nativeEvent;

		let text = '';
		if (e.clipboardData || e.originalEvent.clipboardData) {
			text = (e.originalEvent || e).clipboardData.getData('text/plain');
		} else if (window.clipboardData) {
			text = window.clipboardData.getData('Text');
		}

		if (document.queryCommandSupported('insertText')) {
			// Browsers: Edge, Chrome, Firefox, Safari
			const maxLength = this.props.configuration.interface.input.maxLength - this.getCharactersCount($(this.editableRef.current).html());
			if (maxLength > 0) {
				document.execCommand('insertText', false, text.substring(0, maxLength));
			}
		} else {
			// Browsers: IE 11
			let selection = null;
			if (window.getSelection) {
				selection = window.getSelection();
			} else {
				if (console) {
					console.error('getSelection unsupported');
				}
				return;
			}

			const range = selection.getRangeAt(0);
			range.deleteContents();

			const textNode = document.createTextNode(text);
			range.insertNode(textNode);
			range.selectNodeContents(textNode);
			range.collapse(false);

			selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(range);
		}
	}

	/**
	 * Stores the current range in order to have ability to insert HTML at the previous caret position.
	 */
	storeSelectedRange() {
		if (window.getSelection) {
			this.selection = window.getSelection();
			if (this.selection.rangeCount > 0) {
				this.range = this.selection.getRangeAt(0);
				this.parentNode = this.range.commonAncestorContainer.parentNode;
			} else {
				this.range = null;
				this.parentNode = null;
				this.props.logError('No range selected');
			}
		} else {
			this.props.logError('window.getSelection is not supported');
		}
	}

	triggerChange() {
		this.props.onChange && this.props.onChange(this.convertHTMLToMessage($(this.editableRef.current).html()));
	}

	focus() {
		$(this.editableRef.current).focus();
	}

    render () {
        return <div
	        ref={ this.editableRef }
	        className="wcInput"
			onKeyUp={ this.handleChange }
			onKeyDown={ this.handleKeyDown }
	        onClick={ this.handleClick }
	        onTouchEnd={ this.handleClick }
	        onPaste={ this.handlePaste }
			contentEditable
	        data-placeholder={ this.props.placeholder }
        />;
    }

}

HtmlInput.propTypes = {
	placeholder: PropTypes.string,
	inputRequest: PropTypes.string.isRequired,
	resetRequest: PropTypes.bool,
	onChange: PropTypes.func,
	onSendingRequest: PropTypes.func
};

export default connect(
	state => ({
		configuration: state.configuration
	}),
	{ logError },
	null, { forwardRef: true }
)(HtmlInput);
