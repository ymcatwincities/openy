export const CLOSE_ALERT = 'CLOSE_ALERT';

export const closeAlert = id => ({
  type: CLOSE_ALERT,
  payload: id
});
