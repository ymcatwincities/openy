import React, { Component } from 'react';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-grid.css';
import 'bootstrap/dist/css/bootstrap-reboot.css';
import './style.css';

import Categories from './components/Categories/Categories';
import LocationsList from './components/LocationsList/LocationsList';
import Times from './components/Times/Times';

/**
 * Application entry point.
 */
class App extends Component {
  render() {
    return (
      <div className="App">
        <Categories />
        <LocationsList />
        <Times />
      </div>
    );
  }
}

export default App;
