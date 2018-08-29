import React, {Component} from 'react';
import {connect} from 'react-redux';
import './App.css';
import HeaderAlertItem from './components/HeaderAlertItem/HeaderAlertItem';

import {fetchAlerts} from "./actions/backend";

class App extends Component {


    render() {
        const HeaderAlertItemList = () => {
            if (this.props.header) {
                return Object.keys(this.props.header).map(i => {
                    return this.props.header[i].map(a => {
                        let item = this.props.header[i][a];
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
                <div>
                    <HeaderAlertItemList/>
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

const AlertsApp = connect(
    mapStateToProps,
    mapDispatchToProps
)(App)

export default AlertsApp;
