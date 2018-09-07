import {
  FETCH_ALERTS_BEGIN,
  FETCH_ALERTS_SUCCESS,
  FETCH_ALERTS_FAILURE
} from '../actions/backend';

import { CLOSE_ALERT } from '../actions/helpers';

const initState = {
  loading: false,
  error: null,
  alerts: {}
};

export default function activityTypes(state = initState, action) {
  switch (action.type) {
    case FETCH_ALERTS_BEGIN:
      return {
        ...state,
        loading: true,
        error: null
      };

    case FETCH_ALERTS_SUCCESS:
      const alerts = action.payload;

      return {
        ...state,
        loading: false,
        alerts: alerts
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
            }
            else {
              return state.alerts.header.global[i];
            }
          });
          if (typeof newAlerts.header === 'undefined') {
              newAlerts.header = { global: newHeaderGlobal.filter(Boolean) };
          }
          else {
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
            }
            else {
                return state.alerts.header.local[i];
            }
          });
            if (typeof newAlerts.header === 'undefined') {
                newAlerts.header = { local: newHeaderLocal.filter(Boolean) };
            }
            else {
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
            }
            else {
                return state.alerts.footer.global[i];
            }
          });
            if (typeof newAlerts.footer === 'undefined') {
                newAlerts.footer = { global: newFooterGlobal.filter(Boolean) };
            }
            else {
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
            }
            else {
                return state.alerts.footer.local[i];
            }
          });
            if (typeof newAlerts.footer === 'undefined') {
                newAlerts.footer = { local: newFooterLocal.filter(Boolean) };
            }
            else {
                newAlerts.footer.local = newFooterLocal.filter(Boolean);
            }
        }
      }

      return {
        ...state,
        alerts: newAlerts
      };

    default:
      return state;
  }
}
