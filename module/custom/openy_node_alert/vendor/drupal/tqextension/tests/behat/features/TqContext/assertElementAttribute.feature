@api @debug
Feature: Tq Context
  Scenario: Test "assertElementAttribute" method
    Given I am on the "/" page and HTTP code is "200"
    When I work with elements in "body" region
    Then I should see the "div" element with "id" attribute having "page-wrapper" value
