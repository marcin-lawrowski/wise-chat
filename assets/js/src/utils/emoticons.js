import $ from "jquery";

export default class EmoticonsBuilder {

	get SETS() {
		return {
			1: {
				alias: '01',
				total: 30,
				popup: {
					32: [260, 210],
					64: [400, 250],
					128: [330, 250]
				}
			}
		}
	}

	constructor(configuration) {
		this.configuration = configuration;
	}

	buildSet() {
		const zeroPad = (num, places) => {
		  const numZeroes = places - num.toString().length + 1;
		  if (numZeroes > 0) {
		    return Array(+numZeroes).join("0") + num;
		  }
		  return num
		}

		const custom = this.configuration.custom;
		const set = this.SETS[this.configuration.set];

		this.emoticons = [];

		if ($.isArray(custom)) {
			for (let j = 0; j < custom.length; j++) {
				const emoticon = custom[j];
				const id = emoticon.id;
				this.emoticons.push({
					shortcode: `[emoticon custom="${id}"]`,
					url: emoticon.url,
					urlFull: emoticon.urlFull,
					maxWidth: this.configuration.customEmoticonMaxWidthInPopup > 0 ? this.configuration.customEmoticonMaxWidthInPopup : undefined
				});
			}

			if (this.configuration.customPopupWidth > 0) {
				this.LAYER_WIDTH = this.configuration.customPopupWidth;
			}
			if (this.configuration.customPopupHeight > 0) {
				this.LAYER_HEIGHT = this.configuration.customPopupHeight;
			}
		} else if (set) {
			for (let i = 1; i <= set.total; i++) {
				this.emoticons.push({
					class: 'bg-emot_' + set.alias + '_' + this.configuration.size + '_' + zeroPad(i, 3),
					shortcode: `[emoticon set="${set.alias}" index="${zeroPad(i, 3)}" size="${this.configuration.size}"]`
				});
			}

			this.LAYER_WIDTH = set.popup[this.configuration.size][0];
			this.LAYER_HEIGHT = set.popup[this.configuration.size][1];
		}
	}

}
