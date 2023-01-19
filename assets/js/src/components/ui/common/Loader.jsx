import React from "react";
import PropTypes from "prop-types";

class Loader extends React.Component {

	render() {
		return(
			<div className={ "wcLoaderContainer" + (this.props.center ? ' wcLoaderContainerCenter' : '') }
			     style={ { marginTop: this.props.marginTop ? this.props.marginTop : undefined, marginBottom: this.props.marginBottom ? this.props.marginBottom : undefined } }
			>
				<div className={ "wcLoader" + (' ' + this.props.className)}>
					<div/>
					<div/>
					<div/>
					<div/>
				</div>
				{ this.props.message &&
					<div className="wcLoaderText">
						{ this.props.message }
					</div>
				}
			</div>
		);
	}
}

Loader.propTypes = {
	className: PropTypes.string,
	message: PropTypes.string,
	center: PropTypes.bool,
	marginTop: PropTypes.number,
	marginBottom: PropTypes.number
};

export default Loader;