import React from "react";
import PropTypes from 'prop-types';
import { connect } from "react-redux";
import { sendMessage, prepareImage } from "actions/messages";
import { alertError } from "actions/ui";
import ImageViewer from "utils/image-viewer";
import ProgressBar from "ui/common/plain/ProgressBar";
import Loader from "ui/common/Loader";
import { logError } from "actions/log";

class Attachments extends React.Component {

	get IMAGE_TYPES() {
		return ['jpg', 'jpeg', 'png', 'gif'];
	}

	constructor(props) {
		super(props);

		this.imageViewer = new ImageViewer();
	}

	componentDidUpdate(prevProps) {
		const preparedImageSuccess = this.props.preparedImage !== prevProps.preparedImage && this.props.preparedImage && this.props.preparedImage.success;
		const preparedImageFailure = this.props.preparedImage !== prevProps.preparedImage && this.props.preparedImage && this.props.preparedImage.success === false
		const imageSourceChanged = this.props.imageSource !== prevProps.imageSource && this.props.imageSource;
		const fileSourceChanged = this.props.fileSource !== prevProps.fileSource && this.props.fileSource;

		if (preparedImageSuccess) {
			this.onImagePrepared(this.props.preparedImage.result);
		}

		if (preparedImageFailure) {
			this.props.alertError(this.props.preparedImage.error);
		}

		if (imageSourceChanged) {
			this.onImageUploadFileChange(this.props.imageSource);
		}

		if (fileSourceChanged) {
			this.onFileUploadFileChange(this.props.fileSource);
		}
	}

	addAttachment(type, data, name, url) {
		this.props.onChange(
			[ { type: type, data: data, name: name, url: url } ]
		);
	}

	handleDelete(e, deleteIndex) {
		e.preventDefault();

		this.props.onChange(
			this.props.attachments.filter( (attachment, index) => index !== deleteIndex )
		);
	}

	handleImagePreview(e, data) {
		e.preventDefault();

		this.imageViewer.show(data);
	}

	onImageUploadFileChange(files) {
		if (typeof FileReader === 'undefined') {
			this.props.alertError('FileReader is not supported in this browser');
			return;
		}

		if (files.length === 0) {
			this.props.alertError('No files selected');
			return;
		}

		const fileDetails = files[0];
		if (fileDetails.size && fileDetails.size > this.props.configuration.interface.input.images.sizeLimit) {
			this.props.alertError(this.props.application.i18n.sizeLimitError);
			return;
		}

		if (this.IMAGE_TYPES.indexOf(this.getExtension(fileDetails)) > -1) {
			const fileReader = new FileReader();
			fileReader.onload = event => {
				this.props.prepareImage(event.target.result, this.props.channel.id);
			};
			fileReader.readAsDataURL(fileDetails);
		} else {
			this.props.alertError(this.props.application.i18n.unsupportedTypeOfFile);
		}
	}

	onImagePrepared(preparedImageData) {
		if (preparedImageData && preparedImageData.length > 0) {
			this.addAttachment('image', preparedImageData);
		} else {
			this.props.alertError('Cannot prepare image due to server error');
		}
	}

	onFileUploadFileChange(files) {
		if (typeof FileReader === 'undefined') {
			this.props.alertError('FileReader is not supported in this browser');
			return;
		}

		if (files.length === 0) {
			this.props.alertError('No files selected');
			return;
		}

		const fileDetails = files[0];
		if (this.props.configuration.interface.input.attachments.validFileFormats.indexOf(this.getExtension(fileDetails)) > -1) {
			const fileReader = new FileReader();
			const fileName = fileDetails.name;

			if (fileDetails.size > this.props.configuration.interface.input.attachments.sizeLimit) {
				this.props.alertError(this.props.application.i18n.sizeLimitError);
			} else {
				fileReader.onload = event => {
					this.addAttachment('file', event.target.result, fileName);
				};
				fileReader.readAsDataURL(fileDetails);
			}
		} else {
			this.props.alertError(this.props.application.i18n.unsupportedTypeOfFile);
		}
	}

	getExtension(fileDetails) {
		if (typeof fileDetails.name !== 'undefined') {
			const split = fileDetails.name.split('.');
			if (split.length > 1) {
				return split.pop().toLowerCase();
			}
		}

		return null;
	}

	render() {
		return(
			<div className="wcAttachments">
				{ this.props.preparedImage &&
					<ProgressBar visible={ this.props.preparedImage.inProgress } progress={ this.props.preparedImage.progress } />
				}

				{this.props.processingMessage &&
					<Loader message={ this.props.processingMessage } />
				}
				{ this.props.attachments.map( (attachment, index) =>
					<div key={ index } className="wcAttachment">
						{attachment.type === 'file' &&
							<span>{attachment.name}</span>
						}
						{attachment.type === 'image' &&
							<a href="#" className="wcFunctional" onClick={ e => this.handleImagePreview(e, attachment.data) }>
								<img src={ attachment.data } className="wcImagePreview wcFunctional" alt="Image preview" />
							</a>
						}
						<a href="#" className="wcDelete wcFunctional" onClick={ e => this.handleDelete(e, index) } />
					</div>
				)}
			</div>
		)
	}

}

Attachments.propTypes = {
	channel: PropTypes.object.isRequired,
	configuration: PropTypes.object.isRequired,
	preparedImage: PropTypes.object,
	attachments: PropTypes.array.isRequired,
	onChange: PropTypes.func.isRequired,
	processingMessage: PropTypes.string
};

export default connect(
	(state, ownProps) => ({
		configuration: state.configuration,
		application: state.application,
		preparedImage: state.messages.image[ownProps.channel.id]
	}),
	{ sendMessage, prepareImage, alertError, logError }
)(Attachments);