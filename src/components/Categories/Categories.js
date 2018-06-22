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
import { FaInfoCircle } from 'react-icons/lib/fa';
import Sidebar from '../Sidebar/Sidebar';

import './Categories.css';

/**
 * Renders list categories with radio buttons and "Next" button.
 */
class Categories extends Component {
  render() {
    return (
      <div>
        <Container>
          <Row className={'categories'}>
            <Col md={8}>
              <FormGroup tag="fieldset">
                <h2>Swim Lessons</h2>
                <p>Select you activity type</p>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="mr-auto p-2">
                      <Label check>
                        <Input type="radio" name="radio1" />
                        Parent & Child
                        <p className={'mb-0 ml-2'}>
                          <small>Ages: 6mos-3yrs</small>
                        </p>
                      </Label>
                    </div>
                    <div className="p-2 align-self-center">
                      <FaInfoCircle size={25} />
                    </div>
                  </div>
                </FormGroup>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="mr-auto p-2">
                      <Label check>
                        <Input type="radio" name="radio1" />
                        Preschool Child
                        <p className={'mb-0 ml-2'}>
                          <small>Ages: 3-5 years</small>
                        </p>
                      </Label>
                    </div>
                    <div className="p-2 align-self-center">
                      <FaInfoCircle size={25} />
                    </div>
                  </div>
                </FormGroup>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="mr-auto p-2">
                      <Label check>
                        <Input type="radio" name="radio1" />
                        School-Age Child
                        <p className={'mb-0 ml-2'}>
                          <small>Ages: 5-12yrs</small>
                        </p>
                      </Label>
                    </div>
                    <div className="p-2 align-self-center">
                      <FaInfoCircle size={25} />
                    </div>
                  </div>
                </FormGroup>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="mr-auto p-2">
                      <Label check>
                        <Input type="radio" name="radio1" />
                        Teen & Adult
                        <p className={'mb-0 ml-2'}>
                          <small>Ages: 13yrs and up</small>
                        </p>
                      </Label>
                    </div>
                    <div className="p-2 align-self-center">
                      <FaInfoCircle size={25} />
                    </div>
                  </div>
                </FormGroup>
              </FormGroup>
              <Button
                className={
                  'd-flex m-auto justify-content-center pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'
                }
              >
                Next
              </Button>
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

export default Categories;
