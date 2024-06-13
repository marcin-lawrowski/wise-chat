import React from "react";

/**
 * Renders plain text with shortcodes to HTML.
 */
export default class HtmlShortcodesRenderer {

	get PARSERS() {
		return [{
				regExp: /\[([a-z-]+?)(\s[^\]]*?)?]/g,
				callback: (match, i, parserIndex) => {
					let name = match[1];
					let attributes = {};

					if (match[2]) {
						let results = match[2].matchAll(/(?<attribute>[A-Za-z0-9]+?)="(?<value>[^"]+?)"/gi);
						for (let result of results) {
							attributes[result.groups.attribute] = result.groups.value;
						}
					}

					const rendered = this.rendererConfiguration.onShortcodeRender(name, attributes, this.currentKey++, match[0]);
					if (rendered !== null) {
						return rendered;
					} else {
						return match[0];
					}
				}
			}, {
				regExp: /\n/g,
				callback: (match, i, parserIndex) => {
					return <br key={ this.currentKey++ } />;
				}
			}
		];
	}

	/**
	 * @param {Object} configuration
	 * @param {Object} rendererConfiguration
	 */
	constructor(configuration, rendererConfiguration = {}) {
		this.configuration = configuration;
		this.rendererConfiguration = rendererConfiguration;
		this.currentKey = 0;
		this.parse = this.parse.bind(this);
	}

	toHtml(mixed) {
		return this.parse(mixed, 0);
	}

	escapeRegExp(string) {
		return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
	}

	/**
	 *
	 * @param {String} src Path or URL to file
	 * @param {String} type File's extension
	 * @returns {Boolean}
	 */
	isFile(src, type) {
		return src.toLowerCase().match(new RegExp('\.' + type + '$')) !== null;
	}

	/**
	 * @param {String} string
	 * @param {Number} parserIndex
	 * @returns Array
	 */
	parseString(string, parserIndex) {
		if (this.PARSERS.length <= parserIndex) {
			return [string];
		}
		if (string === '') {
			return [string];
		}

		const matches = [...string.matchAll(this.PARSERS[parserIndex].regExp)];
		if (matches.length === 0) {
			return this.parseString(string, parserIndex + 1);
		}

		const elements = [];
		let lastIndex = 0;
		matches.forEach((match, i) => {
			// parse preceding text if there is any:
			if (match.index > lastIndex) {
				elements.push(...this.parseString(string.substring(lastIndex, match.index), parserIndex + 1));
			}

			const callBackResult = this.PARSERS[parserIndex].callback(match, i, parserIndex);
			elements.push(callBackResult);

			lastIndex = match.index + match[0].length;
		});

		// parse the remaining text if there is any:
		if (string.length > lastIndex) {
			elements.push(...this.parseString(string.substring(lastIndex), parserIndex + 1));
		}

		return elements;
	}

	parse(children, key) {
		if (typeof children === 'string') {
			return this.parseString(children, 0);
		} else if (React.isValidElement(children) && (children.type !== 'a') && (children.type !== 'button')) {
			return React.cloneElement(children, {key: key}, this.parse(children.props.children));
		} else if (Array.isArray(children)) {
			return children.map((child, i) => this.parse(child, i));
		}

		return children;
	}

}