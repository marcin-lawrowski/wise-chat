export function sendUserCommand(id, command, parameters) {
	return function(dispatch, getState, {engine, configuration}) {
		dispatch({ type: "command.send", id: id, data: { inProgress: true, error: undefined, result: undefined, success: undefined } });

		engine.sendUserCommand(command, parameters,
			(result) => {
				dispatch({ type: "command.send", id: id, data: { inProgress: false, success: true, result: result } });
			},
			(error) => {
				dispatch({ type: "command.send", id: id, data: { inProgress: false, success: false, error: error } });
			}
		);
	}
}

export function clearUserCommand(id) {
	return function(dispatch) {
		dispatch({ type: "command.clear", id: id });
	}
}