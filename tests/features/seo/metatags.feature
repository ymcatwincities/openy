@openy @api
Feature: Meta tags
  Ensure that meta tags are present and working for landing pages.

  Scenario: Landing page meta tags
    Given I am logged in as a user with the "Editor" role
    When I go to "/node/add/landing_page"
    And I fill in "Title" with "Basic Landing"
    And I fill in "Page title" with "Random string for page title"
    When I press "Save"
    Then I should see text "Random string for page title" in XML
