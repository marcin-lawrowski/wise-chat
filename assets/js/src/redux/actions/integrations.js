export function requestGIFCategories() {
	return function(dispatch, getState, {engine, configuration}) {
		if (getState().integrations.tenor.search.inProgress) {
			return;
		}

		if (!configuration.interface.input.gifs.apiKey) {
			alert('No Tenor GIF API key set');
		}

		const clientKey = getState().application.user.id;

		const search_url = "https://tenor.googleapis.com/v2/categories" +
			"?key=" + configuration.interface.input.gifs.apiKey +
			"&client_key=" + clientKey +
			"&country=" + configuration.interface.input.gifs.country +
			"&locale=" + configuration.interface.input.gifs.language;

		dispatch({ type: "integrations.tenor.categories", data: { inProgress: true, error: undefined, success: undefined } });
		fetch(search_url)
			.then((res) => res.json())
			.then((data) => {
				dispatch({ type: "integrations.tenor.categories", data: { inProgress: false, success: true, results: data.tags } });
			})
			.catch((error) => dispatch({ type: "integrations.tenor.categories", data: { inProgress: false, success: false, error: error } }));
	}
}

export function searchGIFs(keyword) {
	return function(dispatch, getState, {engine, configuration}) {
		if (getState().integrations.tenor.search.inProgress) {
			return;
		}

		if (!configuration.interface.input.gifs.apiKey) {
			alert('No Tenor GIF API key set');
		}

		const clientKey = getState().application.user.id;

		const search_url = "https://tenor.googleapis.com/v2/search?q=" + encodeURIComponent(keyword) +
			"&key=" + configuration.interface.input.gifs.apiKey +
			"&client_key=" + clientKey +
			"&country=" + configuration.interface.input.gifs.country +
			"&locale=" + configuration.interface.input.gifs.language +
			"&limit=" + configuration.interface.input.gifs.limit +
			'&media_filter=gif,tinygif';

		dispatch({ type: "integrations.tenor.search", data: { keyword: keyword, inProgress: true, error: undefined, success: undefined, next: undefined } });
		fetch(search_url)
			.then((res) => res.json())
			.then((data) => {
				dispatch({ type: "integrations.tenor.search", data: { inProgress: false, success: true, results: data.results, next: data.next } });
			})
			.catch((error) => dispatch({ type: "integrations.tenor.search", data: { inProgress: false, success: false, error: error, next: undefined } }));
	}
}

export function searchNextGIFs(keyword) {
	return function(dispatch, getState, {engine, configuration}) {
		const previousSearch = { ...getState().integrations.tenor.search };
		if (previousSearch.inProgress) {
			return;
		}
		if (!configuration.interface.input.gifs.apiKey) {
			alert('No Tenor GIF API key set');
		}

		const clientKey = getState().application.user.id;
		const lmt = 10;
		const search_url = "https://tenor.googleapis.com/v2/search?q=" + encodeURIComponent(keyword) +
			"&key=" + configuration.interface.input.gifs.apiKey +
			"&client_key=" + clientKey +
			"&country=" + configuration.interface.input.gifs.country +
			"&locale=" + configuration.interface.input.gifs.language +
			"&limit=" + configuration.interface.input.gifs.limit +
			'&pos=' + previousSearch.next +
			'&media_filter=gif,tinygif';

		dispatch({ type: "integrations.tenor.search", data: { inProgress: true, error: undefined, success: undefined, next: undefined } });
		fetch(search_url)
			.then((res) => res.json())
			.then((data) => {
				dispatch({ type: "integrations.tenor.search", data: { inProgress: false, success: true, results: [...previousSearch.results, ...data.results], next: data.next } });
			})
			.catch((error) => dispatch({ type: "integrations.tenor.search", data: { inProgress: false, success: false, error: error, next: undefined } }));
	}
}