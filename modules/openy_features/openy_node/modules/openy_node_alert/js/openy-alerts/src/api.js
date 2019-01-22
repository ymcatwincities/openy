/*global drupalSettings:true*/
/*global drupalSettings b:true*/
/*eslint no-restricted-globals: ["warn", "drupalSettings"]*/
import axios from 'axios';
const apiURL = process.env.REACT_APP_API_URL || '';

export default axios.create({
  baseURL: apiURL + drupalSettings.path.baseUrl,
  auth: {
    username: 'ygtc',
    password: 'openy'
  }
});
