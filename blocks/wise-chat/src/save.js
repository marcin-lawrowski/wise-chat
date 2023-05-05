/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
export default function save({ attributes }) {
	let attributesAltered = { ...attributes };
	if (attributesAltered['show_image_upload_button']) {
		attributesAltered['allow_post_images'] = '1';
		attributesAltered['enable_images_uploader'] = '1';
	} else {
		attributesAltered['allow_post_images'] = '';
		attributesAltered['enable_images_uploader'] = '';
	}
	if (attributesAltered['show_file_upload_button']) {
		attributesAltered['enable_attachments_uploader'] = '1';
	} else {
		attributesAltered['enable_attachments_uploader'] = '';
	}

	delete attributesAltered['show_image_upload_button'];
	delete attributesAltered['show_file_upload_button'];

	const convertValue = attributeValue => {
		if (attributeValue === true) {
			return '1';
		} else if (attributeValue === false) {
			return '0';
		} else {
			return attributeValue
		}
	}
	const shortcodeAttributes = attributesAltered ? Object.keys(attributesAltered).map( key => key + '="' + convertValue(attributesAltered[key]) + '"').join(' ') : '';

	return (
		<p { ...useBlockProps.save() }>
			[wise-chat {shortcodeAttributes}]
		</p>
	);
}
