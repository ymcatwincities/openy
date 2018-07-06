import React, { Component } from 'react';
import {
  Container,
  Row,
  Col,
  Button,
  FormGroup,
  Label,
  Input,
  Breadcrumb,
  BreadcrumbItem,
  Card,
  CardBody,
  CardTitle,
  CardSubtitle,
  CardText
} from 'reactstrap';
import { FaInfoCircle } from 'react-icons/lib/fa';
import './List CategoryLevel.css'
import Sidebar from '../Sidebar/Sidebar';



/**
 * Renders list categories with radio buttons and "Next" button.
 */
class ListCategoryLevel extends Component {
  render() {

    return (
      <div>
        <Container>
          <Row className={'level-list'}>
            <Col md={8}>
              <FormGroup tag="fieldset">
                <Row>
                  <Col md={9}>
                <Breadcrumb>
                  <BreadcrumbItem><a href="#">Swim Lessons</a></BreadcrumbItem>
                  <BreadcrumbItem active>Preschool Child </BreadcrumbItem>
                </Breadcrumb>
                <p>Select your level.</p>
                  </Col>
                  <Col md={3}>
                    <div className={'kids-data'}>
                    <h4>Terri</h4>
                    <h5>Age: 3</h5>
                  </div>
                  </Col>
                </Row>
                <FormGroup check>
                  <div className="d-flex">
                    <div className="mr-auto p-2">
                      <Label check>
                        <Input type="radio" name="radio1" />
                        Swim Basics
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
                        Swim Strokes
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
                        Level 4
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

export default ListCategoryLevel;