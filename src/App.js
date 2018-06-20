import React, { Component } from 'react';
import 'bootstrap/scss/bootstrap.scss';
import 'bootstrap/scss/bootstrap-grid.scss';
import 'bootstrap/scss/bootstrap-reboot.scss';
import './style.scss';
import Categories from "./components/Categories";

/**
 * Application entry point.
 */
class App extends Component {
  render() {
    return (
      <div className="App">
          <Categories />
      </div>
    );
  }
}

export default App;
