import React, { Component } from 'react';
import { connect } from 'react-redux';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import HeaderAlertItem from './components/HeaderAlertItem/HeaderAlertItem';
import Slider from 'react-slick';

import { fetchAlerts } from './actions/backend';

class App extends Component {
  constructor(props) {
    super(props);
    this.next = this.next.bind(this);
    this.previous = this.previous.bind(this);
  }
  next() {
    this.slider.slickNext();
  }
  previous() {
    this.slider.slickPrev();
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
    const HeaderAlertItemList = () => {
      if (this.props.footer) {
        return Object.keys(this.props.footer).map(i => {
          return this.props.footer[i].map(a => {
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
                />
              );
            } else {
              return null;
            }
          });
        });
      } else {
        return null;
      }
    };
    return (
      <div className="App">
        <div
          className={'footer-alerts-list alerts footer-alerts-list-processed'}
        >
          <Slider ref={c => (this.slider = c)} {...sliderSettings}>
            {HeaderAlertItemList()}
          </Slider>
          <div className="container">
            <div className="slick__counter">
              <span className="current" /> of <span className="total" />
            </div>
            <div className="slick__arrow">
              <a
                href="#"
                data-role="none"
                className="slick-prev slick-arrow"
                role="button"
                aria-disabled="true"
                onClick={this.previous}
              >
                Previous
              </a>
              <a
                href="#"
                data-role="none"
                className="slick-next slick-arrow"
                role="button"
                aria-disabled="false"
                onClick={this.next}
              >
                Next
              </a>
            </div>
          </div>
        </div>
      </div>
    );
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
    footer: state.init.alerts.footer
  };
};

const AppFooter = connect(
  mapStateToProps,
  mapDispatchToProps
)(App);

export default AppFooter;
