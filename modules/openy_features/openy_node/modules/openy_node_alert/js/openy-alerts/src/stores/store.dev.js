import { applyMiddleware, createStore, compose } from 'redux';
//import { createLogger } from 'redux-logger';
import thunk from 'redux-thunk';

import reducer from '../reducers/index';

const middleware = [];

middleware.push(thunk);
//middleware.push(createLogger());

// See: https://github.com/zalmoxisus/redux-devtools-extension
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;
const enhancers = composeEnhancers(applyMiddleware(...middleware));

export default createStore(reducer, {}, enhancers);
