import React, {Component} from 'react';
import {connect} from 'react-redux';
import './App.css';
// import './css/style.css';
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import HeaderAlertItem from './components/HeaderAlertItem/HeaderAlertItem';
import Slider from "react-slick";

import {fetchAlerts} from "./actions/backend";

class App extends Component {


    render() {
        var sliderSettings = {
            dots: true,
            infinite: true,
            speed: 500,
            slidesToShow: 1,
            slidesToScroll: 1
        };
        const HeaderAlertItemList = () => {
            if (this.props.header) {
                return Object.keys(this.props.header).map(i => {
                    return this.props.header[i].map(a => {
                        return <HeaderAlertItem key={a.title} label={a.title} iconColor={true} linkTitle={false} description={a.description}/>
                    });
                })
            }
            else {
                return null;
            }

        };
        return (
            <div className="App">
                <div className={'header-alerts-list alerts'}>
                    <Slider {...sliderSettings}>
                        {HeaderAlertItemList()}
                    </Slider>
                </div>
            </div>
        );
    }

    componentDidMount() {
        this.props.fetchAlerts('/locations/ridgedale_ymca');
    }
}

const mapDispatchToProps = dispatch => {
    return {
        fetchAlerts: uri => {
            dispatch(fetchAlerts(uri))
        }
    }
}

const mapStateToProps = state => {
    return {
        alerts: state.init.alerts,
        header: state.init.alerts.header,
        footer: state.init.alerts.footer,
    }
}

const AppHeader = connect(
    mapStateToProps,
    mapDispatchToProps
)(App)

export default AppHeader;
