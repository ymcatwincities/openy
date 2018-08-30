/*global Drupal b:true*/
import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import AppHeader from './AppHeader';
import AppFooter from './AppFooter';
import registerServiceWorker from './registerServiceWorker';

import { Provider } from 'react-redux';
import store from './stores/store';



if (typeof Drupal === 'undefined') {
    ReactDOM.render(<Provider store={store}><AppHeader /></Provider>, document.getElementById('openy_alerts_app_header'));
    // registerServiceWorker();
}

if (typeof Drupal === 'undefined') {
    ReactDOM.render(<Provider store={store}><AppFooter /></Provider>, document.getElementById('openy_alerts_app_footer'));
    // registerServiceWorker();
}
else {
    Drupal.behaviors.openy_alerts_app = {
        attach: (context) => {
            // Insert header region for alerts after <header> element.
            if (document.getElementById('openy_alerts_app_header') !== null) {
                return;
            }
            var e = document.createElement('div');
            e.innerHTML = '<div id="openy_alerts_app_header"></div>';
            while(e.firstChild) {
                document.getElementsByTagName('header')[0].parentNode.insertBefore(e.firstChild, document.getElementsByTagName('header')[0].nextSibling);
            }
            // Render our component.
            ReactDOM.render(<Provider store={store}><AppHeader /></Provider>, document.getElementById('openy_alerts_app_header'));
            // Insert footer region for alerts inside "pre-footer" class element.
            if (document.getElementById('openy_alerts_app_footer') !== null) {
                return;
            }
            var f = document.createElement('div');
            f.innerHTML = '<div id="openy_alerts_app_footer"></div>';
            while(f.firstChild) {
                document.getElementsByClassName("pre-footer")[0].appendChild(f.firstChild);
            }
            ReactDOM.render(<Provider store={store}><AppFooter /></Provider>, document.getElementById('openy_alerts_app_footer'));
        }
    };
}
