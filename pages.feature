@openy @api
Feature: OpenY main pages

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario Outline: Check all main are OK
    When I go to "<page>"
    Then I should get a 200 HTTP response
    Examples:
      | page |
      | / |
      | user |

  @javascript
  Scenario Outline: Check Log In text
    When I go to "<page>"
    Then I should see "Sign In"
    Examples:
      | page |
      | / |
      | user |

  Scenario: Check OpenY Facebook module
    Given I am on homepage
    Then I should get "X-Frame-Options" HTTP header

    Given I go to "/facebook_demo"
    Then I should not get "X-Frame-Options" HTTP header
