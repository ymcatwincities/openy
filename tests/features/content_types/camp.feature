@openy @api
Feature: Camp Content type
  As Admin I want to make sure that Camp content type is available with needed fields.

  Scenario: Create basic Camp
    Given I am logged in as a user with the "Editor" role
    When I go to "/node/add/camp"
    And I fill in "Title" with "Camp One"
    And I fill in the following:
      | URL | /register |
      | Link text | Registration |
    And I fill in the following:
      | Street address | Wood road 115  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 46.293433      |
      | Longitude      | -123.238717    |
      | Phone          | +1234567890    |
    When I press "Save and publish"
    Then I should see the message "Camp Camp One has been created."
