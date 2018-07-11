import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  CardBody,
  CardTitle,
  Button,
  CardText,
  FormGroup,
  Label,
  Input
} from 'reactstrap';

import { FaExclamationCircle } from 'react-icons/lib/fa';
import './ResultsList.css';

/**
 * Renders results packages with radio buttons and "Select package" button.
 */
class ResultsList extends Component {
  render() {
    return (
      <div>
        <Container>
          <Row className={' categories results mb-2'}>
            <Col md={12}>
              <Row>
                <Col md={8}>
                  <h2>Registration</h2>
                  <p>
                    Please provide the details marked with a{' '}
                    <FaExclamationCircle size={20} /> to proceed.
                  </p>
                </Col>
                <Col md={4}>
                  <div className={'kids-data'}>
                    <Button
                      className={
                        'd-flex ml-auto justify-content-center pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'
                      }
                    >
                      Back to results
                    </Button>
                  </div>
                </Col>
              </Row>
              <Card className={'data-time my-3'}>
                <CardBody>
                  <CardTitle className={'text-uppercase mb-0'}>
                    June 18, 2018 - June 28, 2018
                  </CardTitle>
                </CardBody>
              </Card>
              <div className="d-flex">
                <div className="mr-auto p-2">
                  <h5 className={'my-3 location-results font-weight-bold'}>
                    Blaisdell (M-Th)
                  </h5>
                </div>
              </div>
              <Card className={'package-results'}>
                <CardBody>
                  <Row>
                    <Col md={12}>
                      <Row>
                        <Col md={4}>
                          <CardText className="mb-0 font-weight-bold">
                            Swim Lessons
                          </CardText>
                          <CardText className={'mb-0'}>
                            Preschool Child
                          </CardText>
                          <CardText className={'text-muted small'}>
                            Swim Strokes
                          </CardText>
                        </Col>
                        <Col md={4}>
                          <CardText className={'mb-0 font-weight-bold orange'}>
                            1 spot remaining
                          </CardText>
                          <CardText>8:00 - 9:00am</CardText>
                        </Col>
                        <Col md={4}>
                          <FormGroup>
                            <div className={'d-flex'}>
                              <div className={'d-flex align-items-center pr-2'}>
                                <FaExclamationCircle size={20} />
                              </div>

                              <Input
                                type="select"
                                name="select"
                                placeholder="fdfs"
                                id="exampleSelect"
                              >
                                <option value="" disabled selected>
                                  Select Participant
                                </option>
                                <option>Terri</option>
                                <option>Craig</option>
                              </Input>
                            </div>
                            <Label for="exampleSelect">
                              or:{' '}
                              <small>
                                <a href={'#'}> Add New Participant</a>
                              </small>
                            </Label>
                          </FormGroup>
                        </Col>
                      </Row>

                      <hr />

                      <Row>
                        <Col md={4}>
                          <CardText className="mb-0 font-weight-bold">
                            Swim Lessons
                          </CardText>
                          <CardText className={'mb-0'}>Parent & Child</CardText>
                        </Col>
                        <Col md={4}>
                          <CardText className={'mb-0 orange font-weight-bold'}>
                            3 spots remaining
                          </CardText>
                          <CardText>8:15 - 9:15am</CardText>
                        </Col>
                        <Col md={4}>
                          <FormGroup>
                            <div className={'d-flex'}>
                              <div className={'d-flex align-items-center pr-2'}>
                                <FaExclamationCircle size={20} />
                              </div>

                              <Input
                                type="select"
                                name="select"
                                placeholder="fdfs"
                                id="exampleSelect"
                              >
                                <option value="" disabled selected>
                                  Select Participant
                                </option>
                                <option>Terri</option>
                                <option>Craig</option>
                              </Input>
                            </div>
                            <Label for="exampleSelect">
                              or:{' '}
                              <small>
                                <a href={'#'}> Add New Participant</a>
                              </small>
                            </Label>
                          </FormGroup>
                        </Col>
                      </Row>
                    </Col>
                  </Row>
                </CardBody>
              </Card>
            </Col>
            <Col md={12}>
              <div className={'d-flex ml-auto py-4 justify-content-end'}>
                <div className="p-2">
                  <p className={'mb-0 font-weight-bold'}>Members: $94.00</p>
                </div>
                <div>
                  <Button
                    className={'pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'}
                  >
                    Add to cart
                  </Button>
                </div>
              </div>
            </Col>
          </Row>
        </Container>
      </div>
    );
  }
}

export default ResultsList;
