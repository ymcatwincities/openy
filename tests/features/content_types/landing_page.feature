@openy @api
Feature: Landing page Content type
  As Admin I want to make sure that Landing page content type is available with needed fields.

  Scenario: Create basic landing page and check fields
    Given I am logged in as a user with the "Editor" role
    When I go to "/node/add/landing_page"
    And I fill in "Title" with "Basic Landing"
    And I select "One Column" from "Layout"
    Then I should see "Header Area"
    And I should see "Content Area"
    And I should see "Sidebar Area"
    When I press "Save and publish"
    Then I should see the message "Landing Page Basic Landing has been created."
