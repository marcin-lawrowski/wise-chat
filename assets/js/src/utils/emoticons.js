import $ from "jquery";

export default class EmoticonsBuilder {

	get SETS() {
		return {
			1: {
				alias: '01',
				total: 30,
				popup: {
					32: [240, 210],
					64: [240, 250],
					128: [240, 250]
				}
			},
			2: {
				alias: '02',
				total: 50,
				popup: {
					32: [240, 210],
					64: [240, 250],
					128: [240, 250]
				}
			},
			3: {
				alias: '03',
				total: 48,
				popup: {
					32: [240, 210],
					64: [240, 250],
					128: [240, 250]
				}
			},
			4: {
				alias: '04',
				total: 50,
				popup: {
					32: [240, 210],
					64: [240, 250],
					128: [240, 250]
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

		const set = this.SETS[this.configuration.set];

		this.emoticons = [];

		if (set) {
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
