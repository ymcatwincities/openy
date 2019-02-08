import API from '../api';

export const FETCH_ALERTS_BEGIN = 'FETCH_ALERTS_BEGIN';
export const FETCH_ALERTS_SUCCESS = 'FETCH_ALERTS_SUCCESS';
export const FETCH_ALERTS_FAILURE = 'FETCH_ALERTS_FAILURE';

export const fetchAlertsBegin = () => ({
  type: FETCH_ALERTS_BEGIN,
  payload: true
});

export const fetchAlertsSuccess = alerts => ({
  type: FETCH_ALERTS_SUCCESS,
  payload: alerts
});

export const fetchAlertsFailure = error => ({
  type: FETCH_ALERTS_FAILURE,
  payload: error
});

export function fetchAlerts(uri) {
  return dispatch => {
    dispatch(fetchAlertsBegin());
    API.get(`/alerts?uri=${uri}&_format=json`, {})
      .then(response => dispatch(fetchAlertsSuccess(response.data)))
      .catch(error => dispatch(fetchAlertsFailure(error)));
  };
}
