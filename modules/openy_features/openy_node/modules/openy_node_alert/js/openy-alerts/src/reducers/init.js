import cookie from 'react-cookies';

import {
  FETCH_ALERTS_BEGIN,
  FETCH_ALERTS_SUCCESS,
  FETCH_ALERTS_FAILURE
} from '../actions/backend';

import { CLOSE_ALERT } from '../actions/helpers';

const initState = {
  loading: true,
  error: null,
  alerts: {},
  headerPager: false,
  footerPager: false
};

export default function activityTypes(state = initState, action) {
  let newHeaderCount = 0;
  let newFooterCount = 0;
  switch (action.type) {
    case FETCH_ALERTS_BEGIN:
      return {
        ...state,
        loading: true,
        error: null
      };

    case FETCH_ALERTS_SUCCESS:
      const alerts = action.payload;
      let dismissed = cookie.load('alerts_dismiss');
      if (dismissed) {
        if (alerts.header) {
          let alertsHeaderLocal = alerts.header.hasOwnProperty('local')
            ? alerts.header.local.filter((element) => !dismissed.includes(parseInt(element.id)))
            : [];
          alerts.header.local = alertsHeaderLocal;
        }

        if (alerts.footer) {
          let alertsFooterLocal = alerts.footer.hasOwnProperty('local')
            ? alerts.footer.local.filter((element) => !dismissed.includes(parseInt(element.id)))
            : [];
          alerts.footer.local = alertsFooterLocal;
        }
      }

      if (
        typeof alerts.header !== 'undefined' &&
        typeof alerts.header.global !== 'undefined'
      ) {
        newHeaderCount = newHeaderCount + alerts.header.global.length;
      }
      if (
        typeof alerts.header !== 'undefined' &&
        typeof alerts.header.local !== 'undefined'
      ) {
        newHeaderCount = newHeaderCount + alerts.header.local.length;
      }
      if (
        typeof alerts.footer !== 'undefined' &&
        typeof alerts.footer.global !== 'undefined'
      ) {
        newFooterCount = newFooterCount + alerts.footer.global.length;
      }
      if (
        typeof alerts.footer !== 'undefined' &&
        typeof alerts.footer.local !== 'undefined'
      ) {
        newFooterCount = newFooterCount + alerts.footer.local.length;
      }
      return {
        ...state,
        loading: false,
        alerts: alerts,
        headerPager: newHeaderCount >= 2,
        footerPager: newFooterCount >= 2
      };

    case FETCH_ALERTS_FAILURE:
      return {
        ...state,
        loading: false,
        error: action.payload
      };

    case CLOSE_ALERT:
      let newAlerts = {};
      let newHeaderGlobal = {};
      let newHeaderLocal = {};
      let newFooterGlobal = {};
      let newFooterLocal = {};

      if (state.alerts.hasOwnProperty('header')) {
        if (state.alerts.header.hasOwnProperty('global')) {
          newHeaderGlobal = Object.keys(state.alerts.header.global).map(i => {
            if (
              parseInt(state.alerts.header.global[i].id) ===
              parseInt(action.payload)
            ) {
              return null;
            } else {
              return state.alerts.header.global[i];
            }
          });
          if (typeof newAlerts.header === 'undefined') {
            newAlerts.header = { global: newHeaderGlobal.filter(Boolean) };
          } else {
            newAlerts.header.global = newHeaderGlobal.filter(Boolean);
          }
        }
        if (state.alerts.header.hasOwnProperty('local')) {
          newHeaderLocal = Object.keys(state.alerts.header.local).map(i => {
            if (
              parseInt(state.alerts.header.local[i].id) ===
              parseInt(action.payload)
            ) {
              return null;
            } else {
              return state.alerts.header.local[i];
            }
          });
          if (typeof newAlerts.header === 'undefined') {
            newAlerts.header = { local: newHeaderLocal.filter(Boolean) };
          } else {
            newAlerts.header.local = newHeaderLocal.filter(Boolean);
          }
        }
      }

      if (state.alerts.hasOwnProperty('footer')) {
        if (state.alerts.footer.hasOwnProperty('global')) {
          newFooterGlobal = Object.keys(state.alerts.footer.global).map(i => {
            if (
              parseInt(state.alerts.footer.global[i].id) ===
              parseInt(action.payload)
            ) {
              return null;
            } else {
              return state.alerts.footer.global[i];
            }
          });
          if (typeof newAlerts.footer === 'undefined') {
            newAlerts.footer = { global: newFooterGlobal.filter(Boolean) };
          } else {
            newAlerts.footer.global = newFooterGlobal.filter(Boolean);
          }
        }
        if (state.alerts.footer.hasOwnProperty('local')) {
          newFooterLocal = Object.keys(state.alerts.footer.local).map(i => {
            if (
              parseInt(state.alerts.footer.local[i].id) ===
              parseInt(action.payload)
            ) {
              return null;
            } else {
              return state.alerts.footer.local[i];
            }
          });
          if (typeof newAlerts.footer === 'undefined') {
            newAlerts.footer = { local: newFooterLocal.filter(Boolean) };
          } else {
            newAlerts.footer.local = newFooterLocal.filter(Boolean);
          }
        }
      }

      if (
        typeof newAlerts.header !== 'undefined' &&
        typeof newAlerts.header.global !== 'undefined'
      ) {
        newHeaderCount = newHeaderCount + newAlerts.header.global.length;
      }
      if (
        typeof newAlerts.header !== 'undefined' &&
        typeof newAlerts.header.local !== 'undefined'
      ) {
        newHeaderCount = newHeaderCount + newAlerts.header.local.length;
      }
      if (
        typeof newAlerts.footer !== 'undefined' &&
        typeof newAlerts.footer.global !== 'undefined'
      ) {
        newFooterCount = newFooterCount + newAlerts.footer.global.length;
      }
      if (
        typeof newAlerts.footer !== 'undefined' &&
        typeof newAlerts.footer.local !== 'undefined'
      ) {
        newFooterCount = newFooterCount + newAlerts.footer.local.length;
      }
      return {
        ...state,
        alerts: newAlerts,
        headerPager: newHeaderCount >= 2,
        footerPager: newFooterCount >= 2
      };

    default:
      return state;
  }
}
