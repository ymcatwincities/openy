@openy @api
Feature: Branch Content type
  As Admin I want to make sure that Branch content type is available with needed fields.

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario: Create basic branch
    When I go to "/user/login"
    Given I am logged in as a user with the "Administrator" role
    When I go to "/node/add/branch"
    And I fill in "Title" with "Branch One"
    And I fill in the following:
      | Street address | Main road 10   |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 47.293433      |
      | Longitude      | -122.238717    |
      | Phone          | +1234567890    |
    When I press "Save and publish"
    Then I should see "Branch One"
    And I should see " has been created."
