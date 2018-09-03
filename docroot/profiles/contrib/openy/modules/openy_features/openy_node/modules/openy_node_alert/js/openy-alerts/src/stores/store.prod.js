import { applyMiddleware, createStore } from 'redux';
import thunk from 'redux-thunk';

import reducer from '../reducers/index';

const middleware = [thunk];

export default createStore(reducer, {}, applyMiddleware(...middleware));
