/*global location b:true*/
/*global drupalSettings b:true*/
/*eslint no-restricted-globals: ["warn", "drupalSettings"]*/
import React, { Component } from 'react';
import { connect } from 'react-redux';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import HeaderAlertItem from './components/HeaderAlertItem/HeaderAlertItem';
import Slider from 'react-slick';
import cookie from 'react-cookies';

import { fetchAlerts } from './actions/backend';

class App extends Component {
  constructor(props) {
    super(props);
    this.next = this.next.bind(this);
    this.previous = this.previous.bind(this);
    this.goto = this.goto.bind(this);
  }

  next() {
    this.slider.slickNext();
  }

  previous() {
    this.slider.slickPrev();
  }

  goto(num) {
    this.slider.slickGoTo(num);
  }

  componentWillMount() {
    this.state = { alerts_dismiss: cookie.load('alerts_dismiss') };
  }

  render() {
    var sliderSettings = {
      dots: false,
      infinite: false,
      speed: 500,
      slidesToShow: 1,
      slidesToScroll: 1,
      arrows: false,
      variableWidth: false,
      centerMode: true,
      centerPadding: '0px'
    };
    let hil = null;
    const HeaderAlertItemList = () => {
      if (this.props.header) {
        var idx = 0;
        hil = Object.keys(this.props.header).map(i => {
          return this.props.header[i].map(a => {
            if (
              (typeof this.state.alerts_dismiss !== 'undefined' &&
                !this.state.alerts_dismiss.includes(parseInt(a.id))) ||
              typeof this.state.alerts_dismiss === 'undefined'
            ) {
              return (
                <HeaderAlertItem
                  key={parseInt(a.id)}
                  alertId={parseInt(a.id)}
                  label={a.title}
                  iconColor={a.iconColor}
                  linkTitle={a.linkText}
                  linkUrl={a.linkUrl}
                  description={a.description}
                  txtColor={a.textColor}
                  bgColor={a.bgColor}
                  focus={this.goto}
                  index={idx++}
                />
              );
            } else {
              return null;
            }
          });
        });
        hil = hil.filter(Boolean);
        return hil;
      } else {
        return null;
      }
    };
    return (
      <div className="App">
        <div
          className={'header-alerts-list alerts header-alerts-list-processed'}
        >
          <Slider ref={c => (this.slider = c)} {...sliderSettings}>
            {HeaderAlertItemList()}
          </Slider>
          <div className="container">
            <div className="slick__counter hidden">
              <span className="current" /> of <span className="total" />
            </div>
            {this.props.headerPager && (
              <div className="slick__arrow">
                <a
                  tabIndex="-1"
                  data-role="none"
                  className="slick-prev slick-arrow"
                  role="button"
                  aria-disabled="true"
                  aria-label="Previous alert message"
                  onClick={this.previous}
                >
                  Previous alert message
                </a>
                <a
                  tabIndex="-1"
                  data-role="none"
                  className="slick-next slick-arrow"
                  role="button"
                  aria-disabled="false"
                  aria-label="Next alert message"
                  onClick={this.next}
                >
                  Next alert message
                </a>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }

  componentDidMount() {
    let pathname = location.pathname.substring(1);
    let baseUrl = drupalSettings.path.baseUrl;
    if (baseUrl === '/') {
      this.props.fetchAlerts(`/${pathname}`);
      return;
    } else {
      let uri = `//${pathname}`.replace(new RegExp(baseUrl, 'g'), '');
      this.props.fetchAlerts(uri);
    }
  }
}

const mapDispatchToProps = dispatch => {
  return {
    fetchAlerts: uri => {
      dispatch(fetchAlerts(uri));
    }
  };
};

const mapStateToProps = state => {
  return {
    alerts: state.init.alerts,
    header: state.init.alerts.header,
    headerPager: state.init.headerPager
  };
};

const AppHeader = connect(
  mapStateToProps,
  mapDispatchToProps
)(App);

export default AppHeader;
