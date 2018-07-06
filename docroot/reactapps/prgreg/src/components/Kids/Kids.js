import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Button,
  Card,
  CardBody,
  CardTitle,
  CardSubtitle,
  Label,
  Input,
  Table
} from 'reactstrap';

import './Kids.css';
import { FaPlusCircle } from 'react-icons/lib/fa';
import { Locations } from '../Sidebar/Sidebar';

/**
 * Renders list categories with radio buttons and "Next" button.
 */
class Kids extends Component {
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
      <Container>
        <Row className={'categories'}>
          <Col md={8}>
            <h2>Swim Lessons</h2>
          </Col>
          <Col md={4}>{Activities}</Col>
          <Col md={12}>
            <div className={'table-kids'}>
              <Table borderless>
                <thead>
                  <tr>
                    <th />
                    <th>
                      <Label for="name">First Name</Label>
                    </th>
                    <th>
                      <Label for="age">Age</Label>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <th scope="row">1</th>
                    <td>
                      <Input type="name" name="name" id="name" />
                    </td>
                    <td>
                      <Input type="age" name="age" id="age" />
                    </td>
                  </tr>

                  <tr>
                    <th scope="row">2</th>
                    <td colspan="2">
                      <a href={'#'}>
                        <FaPlusCircle size={20} className={'mr-2'} />Add Another
                        Participant
                      </a>
                    </td>
                  </tr>
                </tbody>
              </Table>
              <Button
                className={'ml-5 pt-2 pb-2 pl-5 pr-5 btn-next text-uppercase'}
              >
                Next
              </Button>
            </div>
          </Col>
        </Row>
      </Container>
    );
  }
}

export default Kids;
