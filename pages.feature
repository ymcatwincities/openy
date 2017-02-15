@openy @api
Feature: OpenY main pages

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario Outline: Check all main are OK
    Given that I log in with "admin" and "ffw"
    When I go to "<page>"
    Then I should get a 200 HTTP response
    Examples:
      | page |
      | / |
      | user |

  @javascript
  Scenario Outline: Check Log In text
    When I go to "<page>"
    Then I should see "Log In"
    Examples:
      | page |
      | / |
      | user |
