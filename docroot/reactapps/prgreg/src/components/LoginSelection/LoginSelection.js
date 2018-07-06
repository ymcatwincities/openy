import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Button,
  Card,
  CardBody,
  CardTitle,
  CardSubtitle
} from 'reactstrap';

/**
 * Renders login selection with buttons.
 */
class LoginSelection extends Component {
  render() {
    const Activities = (
      <div>
        <Card>
          <CardBody>
            <div className={'d-flex'}>
              <div className={'mr-auto align-self-center'}>
                <CardTitle className={'text-uppercase'}>Activities</CardTitle>
              </div>
            </div>
            <hr />
            <CardSubtitle className="font-weight-bold">
              Swim Lessons
            </CardSubtitle>
          </CardBody>
        </Card>
      </div>
    );

    return (
      <div>
        <Container>
          <Row className={'categories'}>
            <Col md={8}>
              <h2>Swim Lessons</h2>
            </Col>
            <Col md={4}>{Activities}</Col>
            <Col md={12}>
              <div className="my-4 d-sm-flex justify-content-center">
                <Button
                  className={
                    'mb-3 mr-3 pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'
                  }
                >
                  Sign in
                </Button>
                <Button
                  className={
                    'mb-3 mr-3 pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'
                  }
                >
                  Contine as guest
                </Button>
              </div>
            </Col>
          </Row>
        </Container>
      </div>
    );
  }
}

export default LoginSelection;
