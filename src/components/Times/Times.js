import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Button,
  FormGroup,
  Label,
  Input
} from 'reactstrap';
import Sidebar from '../Sidebar/Sidebar';

/**
 * Renders times with checkboxes and "Next" button.
 */
class Times extends Component {
  render() {
    return (
      <div>
        <Container>
          <Row className={'locations'}>
            <Col md={8}>
              <FormGroup tag="fieldset">
                <h2>Time(s)</h2>
                <p>Select your preferred time(s).</p>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="p-2">
                      <Label check>
                        <Input type="checkbox" />
                        Anytime
                      </Label>
                    </div>
                  </div>
                </FormGroup>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="p-2">
                      <Label check>
                        <Input type="checkbox" />
                        Morning
                        <p className={'mb-0'}>
                          <small>Before 12pm</small>
                        </p>
                      </Label>
                    </div>
                  </div>
                </FormGroup>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="p-2">
                      <Label check>
                        <Input type="checkbox" />
                        Afternoon
                        <p className={'mb-0'}>
                          <small>12-5pm</small>
                        </p>
                      </Label>
                    </div>
                    <div className="m-auto p-2 align-self-center">
                      <p className={'mb-0 orange'}>
                        <small> No options at Blaisdell</small>
                      </p>
                    </div>
                  </div>
                </FormGroup>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="p-2">
                      <Label className={'text-muted'} check>
                        <Input type="checkbox" />
                        Evening
                        <p className={'mb-0'}>
                          <small>After 5pm</small>
                        </p>
                      </Label>
                    </div>
                    <div className="m-auto p-2 align-self-center">
                      <p className={'mb-0 red'}>
                        <small> No availble options</small>
                      </p>
                    </div>
                  </div>
                </FormGroup>
                <Button
                  className={
                    'd-flex m-auto justify-content-center pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'
                  }
                >
                  Next
                </Button>
              </FormGroup>
            </Col>
            <Col md={4} className={'order-first order-md-last'}>
              <Sidebar />
            </Col>
          </Row>
        </Container>
      </div>
    );
  }
}

export default Times;
