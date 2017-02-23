@openy @api
Feature: Camp Content type
  As Admin I want to make sure that Camp content type is available with needed fields.

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario: Create basic Camp
    Given I am logged in as a user with the "Administrator" role
    When I go to "/node/add/camp"
    And I fill in "Title" with "Camp One"
    And I fill in the following:
      | URL | /register |
      | Link text | Registration |
    And I fill in the following:
      | Country        | US             |
      | Street address | Wood road 115  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 46.293433      |
      | Longitude      | -123.238717    |
      | Phone          | +1234567890    |
    When I press "Save and publish"
    Then I should see "Camp One"
    And I should see " has been created."
