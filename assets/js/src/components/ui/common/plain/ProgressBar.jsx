import React from "react";
import PropTypes from "prop-types";

class ProgressBar extends React.Component {

	render() {
		return(
			<progress
				className={`wcMainProgressBar ${ this.props.visible ? '' : 'wcHidden' }`}
				max="100"
				value={ this.props.progress }
			/>
		);
	}
}

ProgressBar.defaultProps = {
	progress: 0,
	visible: false
};

ProgressBar.propTypes = {
	progress: PropTypes.number,
	visible: PropTypes.bool
};

export default ProgressBar;