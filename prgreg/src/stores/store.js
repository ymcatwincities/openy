import { applyMiddleware, createStore } from 'redux';
import { createLogger } from 'redux-logger';
import reducer from '../reducers/index';

import thunk from 'redux-thunk';

const middleware = [thunk];
if (process.env.NODE_ENV !== 'production') {
  middleware.push(createLogger());
}

export default createStore(reducer, {}, applyMiddleware(...middleware));
