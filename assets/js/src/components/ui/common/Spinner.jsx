import React from "react";
import PropTypes from "prop-types";

class Spinner extends React.Component {

	render() {
		return(
			<div className={ "wcLoaderContainer wcSpinner" }
			     style={ { marginTop: this.props.marginTop ? this.props.marginTop : undefined, marginBottom: this.props.marginBottom ? this.props.marginBottom : undefined } }
			>
				<div className={ "wcLoader" + (' ' + this.props.className)}>
					<div/>
					<div/>
					<div/>
					<div/>
				</div>
			</div>
		);
	}
}

Spinner.defaultProps = {
	className: ''
}

Spinner.propTypes = {
	className: PropTypes.string,
	marginTop: PropTypes.number,
	marginBottom: PropTypes.number
};

export default Spinner;