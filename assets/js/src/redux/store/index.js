import { createStore, applyMiddleware, compose } from "redux";
import thunk from "redux-thunk";
import rootReducer from "reducers";

function getStore(engine, configuration) {
	const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

	return createStore(
		rootReducer,
		composeEnhancers(applyMiddleware(thunk.withExtraArgument({engine, configuration})))
	)
}

export default getStore;