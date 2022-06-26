import React from "react";
import ImageViewer from "utils/image-viewer";

/**
 * Renders plain text with shortcodes to HTML.
 */
export default class HtmlRenderer {

	get PARSERS() {
		const emoticonsConfig = this.configuration.interface.input.emoticons;
		const soundsConfig = this.configuration.interface.input.sounds;

		return [{
				regExp: /\[link src="(.+?)"(\s+name="(.+?)")?]/g,
				callback: (match, i, parserIndex) => {
					let url = match[1];

					if (this.configuration.interface.message.links) {
						let finalUrl = (!url.match(/^https|http|ftp|mailto:/) ? "http://" : '') + url;
						if (match[2]) {
							url = match[3];
						}

						return <a key={ this.currentKey++ } href={ finalUrl } target="_blank" rel="noopener noreferrer nofollow" data-org={ btoa(match[0]) }>{ url }</a>;
					} else {
						return url;
					}
				}
			}, {
				regExp: /\[sound id="(.+?)" src="(.+?)" name-org="(.+?)"]/g,
				callback: (match, i, parserIndex) => {
					let attachmentSrc = match[2];

					if (soundsConfig.enabled) {
						return <audio key={ this.currentKey++ } controls data-org={ btoa(match[0]) }>
							<source src={ attachmentSrc } type="audio/mpeg" />
							Your browser does not support the audio element.
						</audio>;
					} else {
						return '';
					}
				}
			}, {
				regExp: /\[attachment id="(.+?)" src="(.+?)" name-org="(.+?)"]/g,
				callback: (match, i, parserIndex) => {
					let attachmentSrc = match[2];
					let linkBody = match[3];

					if (this.configuration.interface.message.attachments) {
						return <a key={ this.currentKey++ } href={ attachmentSrc } target="_blank" rel="noopener noreferrer nofollow" data-org={ btoa(match[0]) }>{ linkBody }</a>;
					} else if (this.configuration.interface.message.links) {
						let finalUrl = (!attachmentSrc.match(/^https|http|ftp:/) ? "http://" : '') + attachmentSrc;

						return <a key={ this.currentKey++ } href={ finalUrl } target="_blank" rel="noopener noreferrer nofollow" data-org={ btoa(match[0]) }>{ linkBody }</a>;
					} else {
						return linkBody;
					}
				}
			}, {
				regExp: /\[img id="(\d+)" src="(.+?)" src-th="(.+?)" src-org="(.+?)"]/g,
				callback: (match, i, parserIndex) => {
					let imageSrc = match[2];
					let imageThumbnailSrc = match[3];
					let imageOrgSrc = match[4];

					if (this.configuration.interface.message.images) {
						return <a
							key={ this.currentKey++ }
							href={ imageSrc }
							target="_blank"
							data-lightbox="wise_chat"
							className="wcFunctional"
							rel="lightbox[wise_chat]"
							data-org={ btoa(match[0]) }
						    onClick={ e => this.handleImagePreview(e, imageSrc) }
						>
							<img src={ imageThumbnailSrc } className="wcImage wcFunctional" alt="Chat image"/>
						</a>;
					} else if (this.configuration.interface.message.links) {
						if (imageOrgSrc === '_') {
							imageOrgSrc = imageSrc;
						}
						let finalUrl = (!imageOrgSrc.match(/^https|http|ftp:/) ? "http://" : '') + imageOrgSrc;

						return <a key={ this.currentKey++ } href={ finalUrl } target="_blank" rel="noopener noreferrer nofollow" data-org={ btoa(match[0]) }>{ imageOrgSrc }</a>;
					} else {
						return imageOrgSrc !== '_' ? imageOrgSrc : imageSrc;
					}
				}
			}, {
				regExp: /\[youtube movie-id="(.+?)" src-org="(.+?)"]/g,
				callback: (match, i, parserIndex) => {
					let movieId = match[1];
					let srcOrg = match[2];

					if (this.configuration.interface.message.yt && movieId.length > 0) {
						return <iframe
									key={ this.currentKey++ }
									width={ this.configuration.interface.message.ytWidth }
									height={ this.configuration.interface.message.ytWidth }
									className="wcVideoPlayer"
									src={ "https://www.youtube.com/embed/" + movieId }
									frameBorder="0" allowFullScreen
									data-org={ btoa(match[0]) }
								/>;
					} else if (this.configuration.interface.message.links && srcOrg.length > 0) {
						let finalUrl = (!srcOrg.match(/^https|http|ftp:/) ? "http://" : '') + srcOrg;

						return <a key={ this.currentKey++ } href={ finalUrl } target="_blank" rel="noopener noreferrer nofollow" data-org={ btoa(match[0]) }>{ srcOrg }</a>;
					} else if (srcOrg.length > 0) {
						return srcOrg;
					}
				}
			}, {
				regExp: /#([^\s#\[\]\\<&;]+)/g,
				callback: (match, i, parserIndex) => {
					let tag = match[1];

					if (this.configuration.interface.message.tt) {
						return <React.Fragment key={ this.currentKey++ }><a href={ 'https://twitter.com/hashtag/' + tag + '?src=hash' } target="_blank" rel="noopener noreferrer nofollow" data-org={ btoa(match[0]) }>#{ tag }</a></React.Fragment>;
					} else {
						return match[0];
					}
				}
			}, {
				regExp: /\[emoticon set="(.+?)" index="(.+?)" size="(\d+)"]/g,
				callback: (match, i, parserIndex) => {
					let setId = match[1];
					let index = match[2];
					let size = match[3];

					if (emoticonsConfig.set) {
						return <img key={ this.currentKey++ }
						            src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
						            alt={'Emoticon set #' + setId + ' ' + size + 'px #' + index }
						            className={'wcFunctional wcEmoticon bg-emot_' + setId + '_' + size + '_' + index }  data-org={ btoa(match[0]) } />;
					} else {
						return <span key={ this.currentKey++ } />;
					}
				}
			}, {
				regExp: /\[emoticon custom="(\d+)"]/g,
				callback: (match, i, parserIndex) => {
					let emoticonId = parseInt(match[1]);

					if (emoticonsConfig.custom) {
						const customEmoticon = emoticonsConfig.custom.find( emoticon => emoticon.id === emoticonId );
						if (customEmoticon) {
							return <img key={ this.currentKey++ }
						            src={ customEmoticon.url }
						            alt={'Emoticon ' + emoticonId }
						            className={'wcFunctional wcEmoticon' }  data-org={ btoa(match[0]) } />;
						} else {
							return <span key={ this.currentKey++ } />;
						}
					} else {
						return <span key={ this.currentKey++ } />;
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
	 */
	constructor(configuration) {
		this.configuration = configuration;
		this.currentKey = 0;
		this.imageViewer = new ImageViewer();
		this.parse = this.parse.bind(this);
		this.handleImagePreview = this.handleImagePreview.bind(this);
	}

	toHtml(mixed) {
		return this.parse(mixed, 0);
	}

	escapeRegExp(string) {
		return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
	}

	handleImagePreview(e, data) {
		e.preventDefault();

		this.imageViewer.show(data);
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