@openy @api
Feature: OpenY main pages

  Scenario Outline: Check all main are OK
    When I go to "<page>"
    Then I should get a 200 HTTP response
    Examples:
      | page |
      | / |
      | user |
