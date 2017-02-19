@openy @api
Feature: Landing page Content type
  As Admin I want to make sure that Landing page content type is available with needed fields.

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario: Create basic landing page and check fields
    Given I am logged in as a user with the "Administrator" role
    When I go to "/node/add/landing"
    And I fill in "Title" with "Basic Landing"
#    And I select "One Column" from "Layout"
    Then I should see "Landing Header"
    And I should see "Body"
    And I should see "Sidebar Content"
    When I press "Save and publish"
    Then I should see "Basic Landing"
    And I should see " has been created."

