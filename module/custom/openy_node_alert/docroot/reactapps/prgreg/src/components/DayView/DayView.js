import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Table,
  Label,
  Input,
  CardText,
  FormGroup
} from 'reactstrap';
import {
  FaChevronRight,
  FaChevronLeft,
  FaQuestionCircle
} from 'react-icons/lib/fa';
import Sidebar from '../Sidebar/Sidebar';

import './DayView.css';

/**
 * Renders list categories with radio buttons and "Next" button.
 */
class DayView extends Component {
  render() {
    return (
      <Container>
        <Row className={'categories'}>
          <Col md={8}>
            <h3>Results</h3>
            <p>The folowing activities are avaialble at Southdale YMCA</p>
            <Row className={'paginate'}>
              <Col md={1} className={'text-center'}>
                <small>
                  <a href={'#'}>
                    <FaChevronLeft />
                    Previous
                  </a>
                </small>
              </Col>
              <Col md={10}>
                <h5 className={'text-center'}>
                  Tuesdays: June 19, 2018 - August 7, 2018
                </h5>
              </Col>
              <Col md={1} className={'text-center'}>
                <small>
                  <a href={'#'}>
                    <FaChevronRight />
                    Next
                  </a>
                </small>
              </Col>
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2} />
              <Col md={5} className={'border-styles'}>
                <p className={'font-weight-bold text-center'}>Terri Age 3</p>
              </Col>
              <Col md={5}>
                <p className={'font-weight-bold text-center'}>Craig Age 3</p>
              </Col>
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>
                  9{' '}
                  <sup>
                    <small>AM</small>
                  </sup>
                </p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>10</p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>11</p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>
                  12{' '}
                  <sup>
                    <small>PM</small>
                  </sup>
                </p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>1</p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>2</p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>3</p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>4</p>
              </Col>
              <Col md={5}>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Preschool Child</CardText>
                    <CardText className={'text-muted small'}>
                      Swim Strokes
                    </CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      4:00 - 4:30<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 green'}>
                      6 Spots Available
                    </CardText>
                  </div>
                </div>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Preschool Child</CardText>
                    <CardText className={'text-muted small'}>
                      Swim Strokes
                    </CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      4:35 - 5:05<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 green'}>
                      4 Spots Available
                    </CardText>
                  </div>
                </div>
              </Col>
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>5</p>
              </Col>
              <Col md={5}>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Preschool Child</CardText>
                    <CardText className={'text-muted small'}>
                      Swim Strokes
                    </CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      5:45- 6:15<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 red text-uppercase mr-3'}>
                      Waitlist
                    </CardText>
                    <FaQuestionCircle />
                  </div>
                </div>
              </Col>
              <Col md={5}>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Parent & Child</CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      5:10- 5:40<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 green'}>
                      4 Spots Available
                    </CardText>
                  </div>
                </div>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Parent & Child</CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      5:45 - 6:15<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 green'}>
                      4 Spots Available
                    </CardText>
                  </div>
                </div>
              </Col>
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>6</p>
              </Col>
              <Col md={5}>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Preschool Child</CardText>
                    <CardText className={'text-muted small'}>
                      Swim Strokes
                    </CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      5:45 - 6:15<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 orange'}>
                      2 Spots Available
                    </CardText>
                  </div>
                </div>
                <div className={'d-flex justify-content-between package'}>
                  <div className={'d-flex align-items-center'}>
                    <Input type="radio" name="radio1" />
                  </div>
                  <div>
                    <CardText className="mb-0 font-weight-bold">
                      Swim Lessons
                    </CardText>
                    <CardText className={'mb-0'}>Preschool Child</CardText>
                    <CardText className={'text-muted small'}>
                      Swim Strokes
                    </CardText>
                  </div>
                  <div>
                    <CardText className={'mb-0'}>
                      6:20 - 6:50<sup>
                        <small>PM</small>
                      </sup>
                    </CardText>
                    <CardText className={'mb-0'}>30 minutes</CardText>
                    <CardText className={'mb-0 orange'}>
                      2 Spots Available
                    </CardText>
                  </div>
                </div>
              </Col>
              <Col md={5} />
            </Row>
            <Row className={'header-day-view'}>
              <Col md={2}>
                <p>
                  7{' '}
                  <sup>
                    <small>PM</small>
                  </sup>
                </p>
              </Col>
              <Col md={5} />
              <Col md={5} />
            </Row>
          </Col>
          <Col md={4}>
            <Sidebar />
          </Col>
        </Row>
      </Container>
    );
  }
}

export default DayView;
