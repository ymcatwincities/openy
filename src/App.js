import React, { Component } from 'react';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-grid.css';
import 'bootstrap/dist/css/bootstrap-reboot.css';
import './style.css';

import Categories from "./components/Categories";
import LocationsList from "./components/LocationsList";

/**
 * Application entry point.
 */
class App extends Component {
  render() {
    return (
      <div className="App">
          <Categories />
          <LocationsList />
      </div>
    );
  }
}

export default App;
