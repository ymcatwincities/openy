import React, { Component } from 'react';
import cookie from 'react-cookies';
import parse from 'html-react-parser';
import { closeAlert } from '../../actions/helpers';
import connect from 'react-redux/es/connect/connect';

/**
 * Renders alert item.
 */
class AlertItem extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    let iconStyle = {
      color: this.props.iconColor ? `#${this.props.iconColor}` : 'blue'
    };

    let closeItem = () => {
      let ad = cookie.load('alerts_dismiss');
      if (typeof ad !== 'undefined') {
        ad.push(parseInt(this.props.alertId));
      } else {
        ad = [parseInt(this.props.alertId)];
      }
      cookie.save('alerts_dismiss', ad);
      this.props.closeAlert(parseInt(this.props.alertId));
    };

    let focusItem = () => {
      this.props.focus(parseInt(this.props.index));
      let slicks = document.getElementsByClassName("slick-list");
      for (let slick of slicks) {
        slick.scrollLeft = 0;
        // Additionally reset horizontal scroll for screen readers.
        setTimeout(() => { slick.scrollLeft = 0; }, 100);
      }
    };

    let linkStyle = {
      color: this.props.txtColor ? `#${this.props.txtColor}` : 'white',
      borderColor: this.props.txtColor ? `#${this.props.txtColor}` : 'white'
    };
    let alertStyle = {
      backgroundColor: this.props.bgColor ? `#${this.props.bgColor}` : 'blue',
      color: this.props.txtColor ? `#${this.props.txtColor}` : 'white'
    };
    let alertContentClasses = this.props.linkTitle ?
      "col-xs-12 col-sm-6 col-md-6 col-lg-6" :
      "col-xs-12 col-sm-11 col-md-11 col-lg-11";
    return (
      <div className="site-alert site-alert--header">
        <div
          role="article"
          data-nid={this.props.alertId}
          style={alertStyle}
          className={`alert${this.props.alertId}`}
          tabindex="0"
          data-idx={this.props.index}
          onFocus={() => focusItem()}
        >
          <div className="container header-alert">
            <div className="row site-alert__wrapper">
              {this.props.iconColor && (
                <div className="site-alert__icon" style={iconStyle}>
                  <i className="fa fa-exclamation-circle" aria-hidden="true" />
                </div>
              )}
              <div className={alertContentClasses}>
                <div className="site-alert__title">
                  {parse(this.props.label)}
                </div>
                <div className="site-alert__content header-alert__content">
                  {parse(this.props.description)}
                </div>
              </div>
              {this.props.linkTitle && (
                <div className="col-xs-12 col-sm-5 col-md-5 col-lg-5 site-alert__cta">
                  <div className="field-alert-link">
                    <a href={this.props.linkUrl} style={linkStyle}>
                      {this.props.linkTitle}
                    </a>
                  </div>
                </div>
              )}

              <a
                href="#close"
                className="site-alert__dismiss"
                onClick={() => closeItem()}
                aria-label="Close alert"
                onFocus={() => focusItem()}
              >
                <i className="fa fa-times" aria-hidden="true">
                  <span className="visually-hidden">
                    Close alert {parse(this.props.label)}
                  </span>
                </i>
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
    closeAlert: id => {
      dispatch(closeAlert(id));
    }
  };
};

const mapStateToProps = state => {
  return {
    alerts: state.init.alerts
  };
};

const HeaderAlertItem = connect(
  mapStateToProps,
  mapDispatchToProps
)(AlertItem);

export default HeaderAlertItem;
