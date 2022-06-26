import React from "react";
import PropTypes from 'prop-types';
import Popup from 'reactjs-popup';
import { connect } from "react-redux";
import { Scrollbar } from "react-scrollbars-custom";
import EmoticonsBuilder from "utils/emoticons";

class EmoticonsPopup extends React.Component {

	constructor(props) {
		super(props);

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

		return (
			<React.Fragment>
				<Popup
					trigger={<button className="wcInputButton wcEmoticon wcFunctional" title={ this.props.i18n.insertEmoticon } />}
					position="top center"
					closeOnDocumentClick
					className={ "wcPopup wcEmoticonsPopup " + this.props.configuration.themeClassName }
					contentStyle={ { width: this.layerWidth, height: this.layerHeight } }
					keepTooltipInside={ this.props.keepInside }
				>
					{close => (
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
					)}
				</Popup>
			</React.Fragment>
		)
	}
}

EmoticonsPopup.propTypes = {
	configuration: PropTypes.object.isRequired,
	onSelect: PropTypes.func.isRequired,
	keepInside: PropTypes.string
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18n: state.application.i18n
	})
)(EmoticonsPopup);