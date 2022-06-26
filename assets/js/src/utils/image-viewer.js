import $ from "jquery";

export default class ImageViewer {

	get HOURGLASS_ICON() {
		return "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAB3RJTUUH3wQEDB4ktAYXpwAAAb5JREFUSMe1lr9qFFEUh78rg8gWW1ikSLEWgkVq2SoYsbBIk1dYEAsxaJt3sLAIFkEEX0FSRlgMhKAPkEIQwZDChATSBLMQP5uz4bKZmZ3ZxR+cYs75nT9z7rlnJpFBfQC8B24xG/4Cz1NK38eKYoKwADxiPiwA1wnSpFUdAO+A+y0D/wBeppQ+5sqihHgAdIBRSumsSWT1bvgcNCF31Et1tWnp6mr4dCZtNw4zpXQB7AJrLdqzBuyGb6OKBuq52m3A7QZ3UGZPVW0CfgJvgc/As4r4H4CnwGvgXkrpDy36uh6VPVRPvYnTsJ2r662HWS3U/ZDH6kkW/CR0Y3sx041Re+qh+kXtq59C+qE7VHt1MWpXQkrpF7ACdIFhZhqGbiU4syX474gWHUU7FjP9YuiOprVo2iF/jUO8U3Hj94NTzJLgVYxgL0v4JqTI3rD9mEZ1v9WN7Hk7G9Pt8d5RN4LbaZPgelWE7JVctL3MXrkqqhLsqFvqbXVoNYbB2VJ32rTnMlbwptOxWbeuyxL0w/GJetUgwVVwVfuT8crGawm4AEbAi4ZdHYXPEvCtrvpl58dy3Rscx9dsnt+W41zxD60+eUN8VNiNAAAAAElFTkSuQmCC";
	}

	constructor() {
		this.hide = this.hide.bind(this);
	}

	setup() {
		if (this.imagePreview) {
			return;
		}

		const container = $('body');
		container.append('<div class="wcImagePreviewLayer wcHide"> </div>');
		this.imagePreview = container.find('.wcImagePreviewLayer');
		this.imagePreview.click(this.hide);
	}

	show(imageSource) {
		const that = this;

		this.setup();
		this.clearRemnants();

		this.addAndShowHourGlass();

		const imageElement = $('<img style="display:none;" src="" alt="Image preview popup" />');
		imageElement.on('load', function() {
			that.removeHourGlass();
			$(this).show();
		});
		imageElement.attr('src', imageSource);
		imageElement.appendTo(this.imagePreview);
		imageElement.click(this.hide);
	}

	hide() {
		this.clearRemnants();
		this.imagePreview.addClass('wcHide');
		this.imagePreview.removeClass('wcShow');
		$('body').removeClass('wcScrollOff');
	}

	clearRemnants() {
		this.imagePreview.find('img').remove();
	}

	addAndShowHourGlass() {
		const imageElement = $('<img class="wcHourGlass" src="" alt="hourglass" />');

		imageElement.attr('src', this.HOURGLASS_ICON);
		imageElement.appendTo(this.imagePreview);

		this.imagePreview.removeClass('wcHide');
		this.imagePreview.addClass('wcShow');
		$('body').addClass('wcScrollOff');
	}

	removeHourGlass() {
		this.imagePreview.find('.wcHourGlass').remove();
	}
}