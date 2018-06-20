import React, {Component} from 'react';
import {Card, CardBody, CardTitle, CardText} from 'reactstrap';


class RightSidebar extends Component {
  render() {
    const Activities = (
      <div>
        <Card>
          <CardBody>
            <CardTitle className={'text-uppercase'}>Activities</CardTitle>
            <hr></hr>
            <CardText>Swim Lessons</CardText>
          </CardBody>
        </Card>
      </div>
    );

    const Locations = (
      <div>
        <Card>
          <CardBody>
            <CardTitle  className={'text-uppercase'}>Locations</CardTitle>
          </CardBody>
        </Card>
      </div>
    );

    const Times = (
      <div>
        <Card>
          <CardBody>
            <CardTitle  className={'text-uppercase'}>Times</CardTitle>
          </CardBody>
        </Card>
      </div>
    );

    return (
      <div>
        {Activities}
        {Locations}
        {Times}
      </div>
    )
  }
}

export default RightSidebar
