@openy @api
Feature: Facility Content type
  As Admin I want to make sure that Facility content type is available with needed fields.

  Scenario: Create basic facility
    Given I am logged in as a user with the "Editor" role
    And I create a "Facility type One" term in the "Facility type" taxonomy
    When I go to "/node/add/facility"
    And I select "Facility type One" from "Type"
    And I fill in "Title" with "Facility One"
    And I fill in the following:
      | Street address | Main road 10   |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 47.293433      |
      | Longitude      | -122.238717    |
      | Phone          | +1234567890    |
    When I press "Save"
    Then I should see the message "Facility Facility One has been created."
