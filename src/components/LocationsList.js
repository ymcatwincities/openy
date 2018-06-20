import React, {Component} from 'react';
import {Container, Row, Col, Button, FormGroup, Label, Input} from 'reactstrap';
import {FaCaretDown} from 'react-icons/lib/fa';
import RightSidebar from "./RightSidebar";

class LocationsList extends Component {
  render() {
    const locations = [
      {name: 'Andover', status: 'unlimit'},
      {name: 'Burnsville', status: 'unlimit'},
      {name: 'Downtown Minneapolis', status: 'limit'},
      {name: 'Elk River', status: 'unlimit'},
      {name: 'Forest Lake', status: 'unlimit'},
      {name: 'Southdale', status: 'unlimit'},
      {name: 'St. Paul Eastside', status: 'unlimit'},
      {name: 'West St. Paul', status: 'unlimit'},
      {name: 'Woodbury', status: 'unlimit'},
      {name: 'Blaisdell', status: 'unlimit'},
      {name: 'Cora McCorvey YMCA', status: 'limit'},
      {name: 'Eagan', status: 'unlimit'},
      {name: 'Emma B Howe - Coon Rapids', status: 'unlimit'},
      {name: 'Hastings', status: 'unlimit'},
      {name: 'St. Poul Downtown', status: 'limit'},
      {name: 'St. Poul Midway', status: 'unlimit'},
      {name: 'White Bear Area', status: 'unlimit'}
    ];

    const Location = locations.map((item, i) => (
        <FormGroup check>
        <div className="d-flex">
          <div className="mr-auto p-2">
            <Label check>
              <Input type="checkbox" />
              {item.name}
              {item.status == 'limit' ?
                <p className={'mb-0 red'}>
                  <small>No available options</small>
                </p> : ''
              }
            </Label>
          </div>
          <div className="p-2 align-self-center">{ item.status == 'unlimit' ? <FaCaretDown size={25} className={'green'} /> : <FaCaretDown size={25} className={'red'} />}</div>
        </div>
      </FormGroup>
      )
    );

    return (
      <div>
        <Container>
          <Row className={'categories'}>
            <Col md={8}>
              <FormGroup tag="fieldset">
                <h2>Location(s)</h2>
                <p>Select your preferred location(s)</p>
                <Row>
                <Col md={6}>
                {Location}
                </Col>
                <Col md={6}>
                {Location}
                </Col>
                </Row>
              </FormGroup>
              <Button className={"d-flex m-auto justify-content-center pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase"}>Next</Button>
            </Col>
            <Col md={4} className={'order-first order-md-last'}>
              <RightSidebar />
            </Col>
          </Row>
        </Container>
      </div>
    );
  }
}

export default LocationsList
