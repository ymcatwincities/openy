import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import registerServiceWorker from './registerServiceWorker';

import { Provider } from 'react-redux';
import store from './stores/store';



if (typeof Drupal === 'undefined') {
    ReactDOM.render(<Provider store={store}><App /></Provider>, document.getElementById('openy_alerts_app'));
    registerServiceWorker();
}
// else {
//     Drupal.behaviors.drupal_block_reactive = {
//         attach: (context) => {
//             // Render our component.
//             ReactDOM.render(<App />, document.getElementById('openy_alerts_app'));
//             registerServiceWorker();
//         }
//     };
// }
