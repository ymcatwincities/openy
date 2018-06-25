import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Card,
  CardBody,
  CardTitle,
  Button,
  FormGroup,
  Label,
  Input,
  CardText
} from 'reactstrap';
import { FaQuestionCircle } from 'react-icons/lib/fa';
import Sidebar from '../Sidebar/Sidebar';
import './ResultsList.css';

/**
 * Renders list categories with radio buttons and "Next" button.
 */
class ResultsList extends Component {
  render() {
    return (
      <div>
        <Container>
          <Row className={'results mb-2'}>
            <Col md={8}>
              <h2>Results</h2>
              <p>The following activities are available.</p>
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
                <div className="p-2 align-self-center">
                  <p className={'mb-0'}>
                    <small>Members: $94.00; Non-Members: $202.00</small>
                  </p>
                </div>
              </div>
              <Card className={'package-results'}>
                <CardBody>
                  <Row>
                    <Col md={8}>
                      <Row>
                        <Col md={7}>
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
                        <Col md={5}>
                          <CardText className={'mb-0 font-weight-bold orange'}>
                            1 spot remaining
                          </CardText>
                          <CardText>8:00 - 9:00am</CardText>
                        </Col>
                      </Row>

                      <hr />

                      <Row>
                        <Col md={7}>
                          <CardText className="mb-0 font-weight-bold">
                            Swim Lessons
                          </CardText>
                          <CardText className={'mb-0'}>Parent & Child</CardText>
                        </Col>
                        <Col md={5}>
                          <CardText className={'mb-0 orange font-weight-bold'}>
                            3 spots remaining
                          </CardText>
                          <CardText>8:00 - 9:00am</CardText>
                        </Col>
                      </Row>
                    </Col>
                    <Col md={4} className={'align-self-center'}>
                      <Button className={'p-2 btn-result text-uppercase'}>
                        Select Package
                      </Button>
                    </Col>
                  </Row>
                </CardBody>
              </Card>

              <div className="d-flex">
                <div className="mr-auto p-2">
                  <h5 className={'my-3 location-results font-weight-bold'}>
                    Southdale (M-Th)
                  </h5>
                </div>
                <div className="p-2 align-self-center">
                  <p className={'mb-0'}>
                    <small>Members: $94.00; Non-Members: $202.00</small>
                  </p>
                </div>
              </div>
              <Card className={'package-results'}>
                <CardBody>
                  <Row>
                    <Col md={8}>
                      <FormGroup check>
                        <Row>
                          <Col md={7}>
                            <Label check>
                              <Input type="radio" name="radio1" />
                              <CardText className="mb-0 font-weight-bold">
                                Swim Lessons
                              </CardText>
                              <CardText className={'mb-0'}>
                                Preschool Child
                              </CardText>
                              <CardText className={'text-muted small'}>
                                Swim Strokes
                              </CardText>
                            </Label>
                          </Col>
                          <Col md={5}>
                            <CardText className={'mb-0 font-weight-bold green'}>
                              6 spots remaining
                            </CardText>
                            <CardText>8:30 - 9:30am</CardText>
                          </Col>
                        </Row>

                        <Row>
                          <Col md={7}>
                            <Label check>
                              <Input type="radio" name="radio1" />
                              <CardText className="mb-0 font-weight-bold">
                                Swim Lessons
                              </CardText>
                              <CardText className={'mb-0'}>
                                Parent & Child
                              </CardText>
                            </Label>
                          </Col>
                          <Col md={5}>
                            <CardText
                              className={'mb-0 font-weight-bold orange'}
                            >
                              2 spots remaining
                            </CardText>
                            <CardText>8:45 - 9:45am</CardText>
                          </Col>
                        </Row>

                        <hr />
                        <Row>
                          <Col md={7}>
                            <CardText className="mb-0 font-weight-bold">
                              Swim Lessons
                            </CardText>
                            <CardText className={'mb-0'}>
                              Parent & Child
                            </CardText>
                          </Col>
                          <Col md={5}>
                            <CardText
                              className={'mb-0 font-weight-bold orange'}
                            >
                              2 spot remaining
                            </CardText>
                            <CardText>8:00 - 9:00am</CardText>
                          </Col>
                        </Row>
                      </FormGroup>
                    </Col>
                    <Col md={4} className={'align-self-center'}>
                      <Button className={'p-2 btn-result text-uppercase'}>
                        Select Package
                      </Button>
                    </Col>
                  </Row>
                </CardBody>
              </Card>

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
                <div className="p-2 align-self-center">
                  <p className={'mb-0'}>
                    <small>Members: $94.00; Non-Members: $202.00</small>
                  </p>
                </div>
              </div>
              <Card className={'package-results'}>
                <CardBody>
                  <Row>
                    <Col md={8}>
                      <Row>
                        <Col md={7}>
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
                        <Col md={5}>
                          <div className={'d-flex'}>
                            <CardText
                              className={
                                'mb-0 font-weight-bold red text-uppercase mr-3'
                              }
                            >
                              Wait list
                            </CardText>
                            <FaQuestionCircle />
                          </div>
                          <CardText>8:00 - 9:00am</CardText>
                        </Col>
                      </Row>
                      <hr />
                      <Row>
                        <Col md={7}>
                          <CardText className="mb-0 font-weight-bold">
                            Swim Lessons
                          </CardText>
                          <CardText className={'mb-0'}>Parent & Child</CardText>
                        </Col>
                        <Col md={5}>
                          <CardText className={'mb-0 font-weight-bold orange'}>
                            5 spots remaining
                          </CardText>
                          <CardText>8:00 - 9:00am</CardText>
                        </Col>
                      </Row>
                    </Col>
                    <Col md={4} className={'align-self-center'}>
                      <Button className={'py-2 btn-result text-uppercase'}>
                        Select Package
                      </Button>
                    </Col>
                  </Row>
                </CardBody>
              </Card>

              <div className="d-flex">
                <div className="mr-auto p-2">
                  <h5 className={'my-3 location-results font-weight-bold'}>
                    Southdale (M-Th)
                  </h5>
                </div>
                <div className="p-2 align-self-center">
                  <p className={'mb-0'}>
                    <small>Members: $94.00; Non-Members: $202.00</small>
                  </p>
                </div>
              </div>
              <Card className={'package-results'}>
                <CardBody>
                  <Row>
                    <Col md={8}>
                      <Row>
                        <Col md={7}>
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
                        <Col md={5}>
                          <CardText className={'mb-0 font-weight-bold green'}>
                            9 spots remaining
                          </CardText>
                          <CardText>8:30 - 9:30am</CardText>
                        </Col>
                      </Row>

                      <hr />
                      <Row>
                        <Col md={7}>
                          <CardText className="mb-0 font-weight-bold">
                            Swim Lessons
                          </CardText>
                          <CardText className={'mb-0'}>Parent & Child</CardText>
                        </Col>
                        <Col md={5}>
                          <CardText className={'mb-0 font-weight-bold orange'}>
                            2 spots remaining
                          </CardText>
                          <CardText>8:00 - 9:00am</CardText>
                        </Col>
                      </Row>
                    </Col>
                    <Col md={4} className={'m-auto align-self-center'}>
                      <Button className={'p-2 btn-result text-uppercase'}>
                        Select Package
                      </Button>
                    </Col>
                  </Row>
                </CardBody>
              </Card>
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

export default ResultsList;
