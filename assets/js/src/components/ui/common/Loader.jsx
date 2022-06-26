import React from "react";
import PropTypes from "prop-types";

class Loader extends React.Component {

	render() {
		return(
			<div className="wcLoaderContainer">
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
	message: PropTypes.string
};

export default Loader;