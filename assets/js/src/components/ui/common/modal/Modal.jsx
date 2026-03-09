import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import Popup from 'reactjs-popup';

class Modal extends React.Component {

	constructor(props, context) {
		super(props, context);

		this.close = this.close.bind(this);
	}

	close(e) {
		if (e) {
			e.preventDefault();
		}
		if (this.props.closable) {
			this.props.onClose();
		}
	}

	render() {
		return <React.Fragment>
			<Popup
				open={ this.props.open }
				className={ "wcPopup wcModal " + this.props.configuration.themeClassName + ' wcModal-' + this.props.size + ' ' + this.props.className }
				modal
				nested
				onClose={ this.props.onClose }
				closeOnDocumentClick={ this.props.closeOnDocumentClick }
			>
				<div className="wcModalContent">
			        <div className="wcHeader"><div className="wcTitle">{ this.props.title }</div> <a href="#" className="wcCloseButton" title={ this.props.i18n.close } onClick={ this.close }>&times;</a></div>
		            <div className="wContent">
			            { this.props.children }
		            </div>
					<div className="wcFooter">
						{ this.props.footer }
						{ this.props.footerCloseButtonVisible && <button className="wcButton" onClick={ this.close }>{ this.props.closeButtonLabel }</button> }
					</div>
		        </div>
			</Popup>
		</React.Fragment>
	}

}

Modal.defaultProps = {
	footerCloseButtonVisible: true,
	closeButtonLabel: 'Cancel',
	footer: [],
	closable: true,
	open: false,
	closeOnDocumentClick: true,
	size: 'md',
	className: '',
	title: ''
}

Modal.propTypes = {
	configuration: PropTypes.object.isRequired,
	className: PropTypes.string.isRequired,
	footerCloseButtonVisible: PropTypes.bool.isRequired,
	title: PropTypes.string.isRequired,
	footer: PropTypes.array.isRequired,
	closeButtonLabel: PropTypes.string.isRequired,
	closable: PropTypes.bool.isRequired,
	closeOnDocumentClick: PropTypes.bool.isRequired,
	open: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	size: PropTypes.string.isRequired
};

export default connect(
	(state) => ({
		configuration: state.configuration,
		i18n: state.application.i18n
	})
)(Modal);