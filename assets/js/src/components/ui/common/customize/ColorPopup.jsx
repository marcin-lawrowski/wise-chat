import React from "react";
import PropTypes from 'prop-types';
import Popup from 'reactjs-popup';
import {connect} from "react-redux";

class ColorPopup extends React.Component {

	get COLORS() {
		return [
			'330000', '331900', '333300', '193300', '003300', '003319', '003333', '001933',
			'000033', '190033', '330033', '330019', '000000', '660000', '663300', '666600', '336600',
			'006600', '006633', '006666', '003366', '000066', '330066', '660066', '660033', '202020',
			'990000', '994c00', '999900', '4c9900', '009900', '00994c', '009999', '004c99', '000099',
			'4c0099', '990099', '99004c', '404040', 'cc0000', 'cc6600', 'cccc00', '66cc00', '00cc00',
			'00cc66', '00cccc', '0066cc', '0000cc', '6600cc', 'cc00cc', 'cc0066', '606060', 'ff0000',
			'ff8000', 'ffff00', '80ff00', '00ff00', '00ff80', '00ffff', '0080ff', '0000ff', '7f00ff',
			'ff00ff', 'ff007f', '808080', 'ff3333', 'ff9933', 'ffff33', '99ff33', '33ff33', '33ff99',
			'33ffff', '3399ff', '3333ff', '9933ff', 'ff33ff', 'ff3399', 'a0a0a0', 'ff6666', 'ffb266',
			'ffff66', 'b2ff66', '66ff66', '66ffb2', '66ffff', '66b2ff', '6666ff', 'b266ff', 'ff66ff',
			'ff66b2', 'c0c0c0', 'ff9999', 'ffcc99', 'ffff99', 'ccff99', '99ff99', '99ffcc', '99ffff',
			'99ccff', '9999ff', 'cc99ff', 'ff99ff', 'ff99cc', 'e0e0e0', 'ffcccc', 'ffe5cc', 'ffffcc',
			'e5ffcc', 'ccffcc', 'ccffe5', 'ccffff', 'cce5ff', 'ccccff', 'e5ccff', 'ffccff', 'ffcce5',
			'ffffff'
		];
	}

	constructor(props) {
		super(props);

		this.handleClick = this.handleClick.bind(this);
	}

	handleClick(e, colorHex) {
		e.preventDefault();

		this.props.onSelect('#' + colorHex);
	}

	render() {
		return (
			<Popup
				trigger={<button className="wcColorSelect wcFunctional" title="Select color" style={ { backgroundColor: this.props.color } } />}
				position="top center"
				className={ "wcPopup wcColorsPopup " + this.props.configuration.themeClassName }
			>
				{close => (
					<div>
						{ this.COLORS.map( (colorHex, index) =>
							<a
								href="#"
								key={ index }
								title={ colorHex }
								onClick={ e => { this.handleClick(e, colorHex); close(); } }
								style={ { backgroundColor: '#' + colorHex } }
							/>
						)}
					</div>
				)}
			</Popup>
		)
	}
}

ColorPopup.propTypes = {
	onSelect: PropTypes.func.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration
	})
)(ColorPopup);