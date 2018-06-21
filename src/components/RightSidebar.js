import React, {Component} from 'react';
import {Card, CardBody, CardTitle, CardText, CardSubtitle} from 'reactstrap';

class RightSidebar extends Component {
  render() {
    const Activities = (
      <div>
        <Card>
          <CardBody>
            <div className={'d-flex'}>
              <div className={'mr-auto align-self-center'}>
                <CardTitle className={'text-uppercase'}>Activities</CardTitle>
              </div>
              <a href={'#'}>
                <small>Add</small>
              </a>
            </div>
            <hr></hr>
            <CardSubtitle className='font-weight-bold'>Swim Lessons</CardSubtitle>
            <CardText>Preschool Child</CardText>
            <CardText className={'text-muted small'}>Swim Strokes</CardText>
            <ul className={'pl-0 list-inline"'}>
              <li className={'list-inline-item'}>
                <a href={'#'}>
                  <small>Edit</small>
                </a>
              </li>
              <li className={'list-inline-item'}><a href={'#'}>
                <small>Delete</small>
              </a></li>
            </ul>

            <CardSubtitle className='font-weight-bold'>Swim Lessons</CardSubtitle>
            <CardText>Parent & Child</CardText>
            <ul className={'pl-0 list-inline"'}>
              <li className={'list-inline-item'}>
                <a href={'#'}>
                  <small>Edit</small>
                </a>
              </li>
              <li className={'list-inline-item'}><a href={'#'}>
                <small>Delete</small>
              </a></li>
            </ul>
          </CardBody>
        </Card>
      </div>
    );

    const Locations = (
      <div>
        <Card>
          <CardBody>
            <CardTitle className={'text-uppercase'}>Locations</CardTitle> <hr></hr>
            <CardSubtitle className='pb-2 pt-2 font-weight-bold'>Blaisdell</CardSubtitle>
            <CardSubtitle className='pb-2 pt-2 font-weight-bold'>Southdale</CardSubtitle>
          </CardBody>
        </Card>
      </div>
    );

    const Times = (
      <div>
        <Card>
          <CardBody>
            <CardTitle className={'text-uppercase'}>Times</CardTitle>
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
