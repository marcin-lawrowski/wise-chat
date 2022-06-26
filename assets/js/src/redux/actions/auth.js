export function sendAuth(mode, parameters) {
	return function(dispatch, getState, {engine, configuration}) {
		dispatch({ type: "auth.send", mode: mode, data: { inProgress: true, error: undefined, result: undefined, success: undefined } });

		engine.auth(mode, parameters,
			(result) => {
				dispatch({ type: "auth.send", mode: mode, data: { inProgress: false, success: true, result: result } });
			},
			(error) => {
				dispatch({ type: "auth.send", mode: mode, data: { inProgress: false, success: false, error: error } });
			}
		);
	}
}

export function clearAuth(mode) {
	return function(dispatch) {
		dispatch({ type: "auth.clear", mode: mode });
	}
}