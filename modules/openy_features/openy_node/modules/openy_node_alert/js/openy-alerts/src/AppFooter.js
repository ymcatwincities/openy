import React, { Component } from 'react';
import { connect } from 'react-redux';
import 'slick-carousel/slick/slick.css';
import 'slick-carousel/slick/slick-theme.css';
import FooterAlertItem from './components/FooterAlertItem/FooterAlertItem';
import Slider from 'react-slick';
import cookie from 'react-cookies';

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
    const FooterAlertItemList = () => {
      if (this.props.footer) {
        hil = Object.keys(this.props.footer).map(i => {
          return this.props.footer[i].map(a => {
            if (
              (typeof this.state.alerts_dismiss !== 'undefined' &&
                !this.state.alerts_dismiss.includes(parseInt(a.id))) ||
              typeof this.state.alerts_dismiss === 'undefined'
            ) {
              return (
                <FooterAlertItem
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
        hil = hil.filter(Boolean);
        return hil;
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
            {FooterAlertItemList()}
          </Slider>
          <div className="container">
            <div className="slick__counter hidden">
              <span className="current" /> of <span className="total" />
            </div>
            {this.props.footerPager && (
              <div className="slick__arrow">
                <a
                  href="#previous"
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
                  href="#next"
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
    footer: state.init.alerts.footer,
    footerPager: state.init.footerPager
  };
};

const AppFooter = connect(
  mapStateToProps,
  mapDispatchToProps
)(App);

export default AppFooter;
