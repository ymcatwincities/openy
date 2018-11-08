@openy @api
Feature: Open Y main pages

  Scenario: Check homepage is available
    When I go to "/"
    Then I should get a 200 HTTP response

  Scenario: Check user page is available
    When I go to "user"
    Then I should get a 200 HTTP response

  Scenario: Check Log In text on homepage
    When I go to "/"
    Then I should see "Log In"

  Scenario: Check Log In text on user page
    When I go to "user"
    Then I should see "Log In"

