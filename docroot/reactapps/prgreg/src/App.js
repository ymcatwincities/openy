import React, { Component } from 'react';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-grid.css';
import 'bootstrap/dist/css/bootstrap-reboot.css';
import './style.css';

import Categories from './components/Categories/Categories';
import LocationsList from './components/LocationsList/LocationsList';
import Times from './components/Times/Times';
import ResultsList from './components/ResultsList/ResultsList';
import LoginSelection from './components/LoginSelection/LoginSelection';
import Kids from './components/Kids/Kids';
import ListCategoryLevel from './components/ListCategoryLevel/ListCategoryLevel';
import DayView from './components/DayView/DayView';

/**
 * Application entry point.
 */
class App extends Component {
  render() {
    return (
      <div className="App">
        <LoginSelection />
        <Kids />
        <Categories />
        <ListCategoryLevel />
        <LocationsList />
        <DayView />
        <Times />
        <ResultsList />
      </div>
    );
  }
}

export default App;
