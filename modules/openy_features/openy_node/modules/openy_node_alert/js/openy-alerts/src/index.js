/*global Drupal b:true*/
/* eslint-disable import/first */
/*global location b:true*/
/*eslint no-restricted-globals: ["warn", "drupalSettings"]*/
/*global drupalSettings b:true*/
console.log('OpenY Alerts initialising...');

import 'babel-polyfill';
import React from 'react';
import ReactDOM from 'react-dom';
import AppHeader from './AppHeader';
import AppFooter from './AppFooter';

import { Provider } from 'react-redux';
import store from './stores/store';

if (typeof Drupal === 'undefined') {
  ReactDOM.render(
    <Provider store={store}>
      <AppHeader />
    </Provider>,
    document.getElementById('openy_alerts_app_header')
  );
}

if (typeof Drupal === 'undefined') {
  ReactDOM.render(
    <Provider store={store}>
      <AppFooter />
    </Provider>,
    document.getElementById('openy_alerts_app_footer')
  );
} else {
  Drupal.behaviors.openy_alerts_app = {
    attach: context => {
      let header_placeholder = document.querySelector('#openy_alerts_header_placeholder');
      let footer_placeholder = document.querySelector('#openy_alerts_footer_placeholder');
      // Do not start on admin urls.
      if (
        location.pathname.substring(1).startsWith('admin') ||
        drupalSettings.path.currentPathIsAdmin === true
      ) {
        return;
      }
      // Insert header region for alerts after <header> element.
      if (document.getElementById('openy_alerts_app_header') !== null) {
        console.log('OpenY Alerts openy_alerts_app_header found...');
        return;
      }
      if (document.getElementById('openy_alerts_app_footer') !== null) {
        console.log('OpenY Alerts openy_alerts_app_footer found...');
        return;
      }

      var e = document.createElement('div');
      e.innerHTML = '<div id="openy_alerts_app_header"></div>';
      while (e.firstChild) {
        if (header_placeholder) {
          header_placeholder.appendChild(
              e.firstChild,
              document.getElementsByTagName('header')[0].nextSibling
          );
        }
        else {
          document
              .getElementsByTagName('header')[0]
              .parentNode.insertBefore(
              e.firstChild,
              document.getElementsByTagName('header')[0].nextSibling
          );
        }
      }
      // Render our component.
      ReactDOM.render(
        <Provider store={store}>
          <AppHeader />
        </Provider>,
        document.getElementById('openy_alerts_app_header')
      );
      // Insert footer region for alerts inside "pre-footer" class element.
      console.log('OpenY Alerts header initialised...');
      var f = document.createElement('div');
      f.innerHTML = '<div id="openy_alerts_app_footer"></div>';
      if (document.getElementsByClassName('pre-footer').length !== 0) {
        // Lily theme...
        while (f.firstChild) {
          if (footer_placeholder) {
            footer_placeholder.appendChild(
              f.firstChild,
              document.getElementsByClassName('pre-footer')[0].nextSibling
            );
          }
          else {
            document
              .getElementsByClassName('pre-footer')[0]
              .parentNode.insertBefore(
                f.firstChild,
                document.getElementsByClassName('pre-footer')[0].nextSibling
              );
          }
        }
        console.log('OpenY Alerts footer Lily initialised...');
        ReactDOM.render(
          <Provider store={store}>
            <AppFooter />
          </Provider>,
          document.getElementById('openy_alerts_app_footer')
        );
      } else {
        // Append after main anchor.
        while (f.firstChild) {
          document
            .getElementById('main')
            .parentNode.insertBefore(
              f.firstChild,
              document.getElementById('main').nextSibling
            );
        }
        console.log('OpenY Alerts footer YGTC initialised...');
        ReactDOM.render(
          <Provider store={store}>
            <AppFooter />
          </Provider>,
          document.getElementById('openy_alerts_app_footer')
        );
      }
    }
  };
}
