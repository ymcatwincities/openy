@openy @api
Feature: Branch Content type
  As Admin I want to make sure that Branch content type is available with needed fields.

  Scenario: Create basic branch
    Given I am logged in as a user with the "Editor" role
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
    Then I should see the message "Branch Branch One has been created."
