import React from "react";
import PropTypes from 'prop-types';
import Popup from 'reactjs-popup';
import { connect } from "react-redux";
import { Scrollbar } from "react-scrollbars-custom";
import EmoticonsBuilder from "utils/emoticons";

class EmoticonsPopup extends React.Component {

	constructor(props) {
		super(props);

		this.state = {
			tab: 'emoticons'
		}

		this.handleClick = this.handleClick.bind(this);

		const emoticonsBuilder = new EmoticonsBuilder(this.props.configuration.interface.input.emoticons);
		emoticonsBuilder.buildSet();

		this.layerWidth = emoticonsBuilder.LAYER_WIDTH;
		this.layerHeight = emoticonsBuilder.LAYER_HEIGHT;
		this.emoticons = emoticonsBuilder.emoticons;
	}

	handleClick(e, emoticon) {
		e.preventDefault();

		this.props.onSelect(emoticon.shortcode);
	}

	render() {
		const custom = this.props.configuration.interface.input.emoticons.custom;
		const categoriesEnabled = this.props.configuration.interface.input.gifs.enabled;
		const layerHeight = this.layerHeight + (categoriesEnabled ? 50 : 0);
		let layerWidth = this.props.windowSizeClass !== 'XXs' ? this.layerWidth : '80%';

		return (
			<React.Fragment>
				<Popup
					trigger={<button className="wcInputButton wcEmoticon wcFunctional" title={ this.props.i18n.insertEmoticon } />}
					position="top center"
					lockScroll
					closeOnDocumentClick
					modal={ this.props.windowSizeClass === 'XXs' }
					className={ "wcPopup wcEmoticonsPopup " + this.props.configuration.themeClassName }
					contentStyle={ { width: layerWidth, height: layerHeight } }
					keepTooltipInside={ true }
				>
					{close => (
						<div className="wcAddonsLibrary">
							{ categoriesEnabled &&
								<div className="wcCategories">
									<a href="#" className={ "wcFunctional wcCategoryButton " + (this.state.tab === 'emoticons' ? 'wcCategoryButtonActive' : '') } onClick={ e => { e.preventDefault(); this.setState({ tab: 'emoticons' }); } }><span className="wcIcon wcIconEmotion" /></a>
									<a href="#" className={ "wcFunctional wcCategoryButton " + (this.state.tab === 'gifs' ? 'wcCategoryButtonActive' : '') } onClick={ e => { e.preventDefault(); this.setState({ tab: 'gifs' }); } }><span className="wcIcon wcIconGif" /></a>
								</div>
							}

							<div className={ 'wcCategory wcCategoryEmoticons ' + (this.state.tab !== 'emoticons' ? 'wcInvisible' : '') }>
								<Scrollbar noScrollX={ true }>
									{ custom && this.emoticons.map( (emoticon, index) =>
										<a href="#" key={ index } onClick={ e => { this.handleClick(e, emoticon); close(); } }>
											<img src={ emoticon.urlFull } style={ emoticon.maxWidth ? { maxWidth: emoticon.maxWidth } : undefined } />
										</a>
									)}
									{ !custom && this.emoticons.map( (emoticon, index) =>
										<a href="#" key={ index } onClick={ e => { this.handleClick(e, emoticon); close(); } }>
											<span className={ 'wcEmoticon ' + emoticon.class } />
										</a>
									)}
								</Scrollbar>
							</div>
						</div>
					)}
				</Popup>
			</React.Fragment>
		)
	}
}

EmoticonsPopup.propTypes = {
	configuration: PropTypes.object.isRequired,
	onSelect: PropTypes.func.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18n: state.application.i18n,
		windowWidth: state.ui.properties.windowWidth,
		windowSizeClass: state.ui.properties.windowSizeClass
	})
)(EmoticonsPopup);