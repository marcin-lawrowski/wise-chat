import Engine from "./Engine";
import AjaxEngine from "./ajax/AjaxEngine";

export default class EngineFactory {

	/**
	 * @param {Object} configuration
	 */
	constructor(configuration) {
		this.configuration = configuration;
	}

	/**
	 * @returns {Engine}
	 */
	createEngine() {
		return new AjaxEngine(this.configuration);
	}

}