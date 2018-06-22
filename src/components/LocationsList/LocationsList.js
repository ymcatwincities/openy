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
import { FaCaretDown } from 'react-icons/lib/fa';
import Sidebar from '../Sidebar/Sidebar';
import './LocationList.css';

/**
 * Renders list locations with checkboxes and "Next" button.
 */
class LocationsList extends Component {
  render() {
    return (
      <div>
        <Container>
          <Row className={'locations'}>
            <Col md={8}>
              <FormGroup tag="fieldset">
                <h2>Location(s)</h2>
                <p>Select your preferred location(s)</p>
                <Row>
                  <Col md={6}>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Andover
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Burnsville
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label className={'text-muted'} check>
                            <Input type="checkbox" />
                            Downtown Minneapolis
                            <p className={'mb-0 red'}>
                              <small>No available options</small>
                            </p>
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'red'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Elk River
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Forest Lake
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Southdale
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            St. Paul Eastside
                            <p className={'mb-0 orange'}>
                              <small>Limited options</small>
                            </p>
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'orange'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            West St. Paul
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Woodbury
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                  </Col>
                  <Col md={6}>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Blaisdell
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label className={'text-muted'} check>
                            <Input type="checkbox" />
                            Cora McCorvey YMCA
                            <p className={'mb-0 red'}>
                              <small>No available options</small>
                            </p>
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'red'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Eagan
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Emma B Howe - Coon Rapids
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            Hastings
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label className={'text-muted'} check>
                            <Input type="checkbox" />
                            St. Poul Downtown
                            <p className={'mb-0 red'}>
                              <small>No available options</small>
                            </p>
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'red'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            St. Poul Midway
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                    <FormGroup check>
                      <div className="d-flex">
                        <div className="mr-auto p-2">
                          <Label check>
                            <Input type="checkbox" />
                            White Bear Area
                          </Label>
                        </div>
                        <div className="p-2 align-self-center">
                          <FaCaretDown size={25} className={'green'} />
                        </div>
                      </div>
                    </FormGroup>
                  </Col>
                </Row>
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

export default LocationsList;
