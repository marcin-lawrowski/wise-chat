import React from "react";
import ReactDOM from "react-dom";
import { Provider } from "react-redux";
import getStore from "store";
import Application from "Application";
import * as Actions from "actions/configuration";
import { CookiesProvider } from 'react-cookie';
import EngineFactory from "engine/EngineFactory";
import EngineStoreDispatcher from "engine/EngineStoreDispatcher";
import {installXhrProgressEvent} from "utils/extensions";
import {getAncestorBackgroundColor} from "utils/html";
import matchAll from 'string.prototype.matchall'

function renderApplication(element, configuration) {
	const engine = (new EngineFactory(configuration)).createEngine();
	const store = getStore(engine, configuration);
	store.dispatch(Actions.replace(configuration));

	const engineStoreDispatcher = new EngineStoreDispatcher(engine, store);

	ReactDOM.render(
		<Provider store={ store }>
			<CookiesProvider>
				<Application rootElement={ element } engine={ engine } />
			</CookiesProvider>
		</Provider>,
		element
	);
}

jQuery(window).on('load', function() {
	matchAll.shim(); // Edge missing matchAll method

	jQuery(".wcContainer[data-wc-config]").each(function() {
		let config = jQuery(this).data('wc-config');

		if (typeof config !== 'object') {
			jQuery(this).html('<strong style="color:#f00;">Error: invalid Wise Chat configuration</strong>');
			return;
		}

		installXhrProgressEvent();
		config.defaultBackgroundColor = config.theme.length === 0 ? getAncestorBackgroundColor(jQuery(this)) : null;

		renderApplication(jQuery(this)[0], config);
	});
});