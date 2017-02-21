@openy @api
Feature: Location finder
  I want to make sure that Location Finder Page shows new branches

  Background: Create basic branch
    Given I am logged in as a user with the "Administrator" role
    And "area" terms:
     |name |
     |White village|
    When I go to "/node/add/branch"
    And I fill in "Title" with "Branch One"
    And I fill in the following:
      | Street address | Main road 10   |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Neighborhood   | White village  |
      | Latitude       | 47.293433      |
      | Longitude      | -122.238717    |
      | Phone          | +1234567890    |
    When I press "Save and publish"

  Scenario: Add location finder page and check display
    When I go to "/node/add/landing_page"
    And I fill in "Title" with "Open locations"
    And I select "One Column" from "Layout"
    And I press "Add Location finder filters" in the "header_area"
    And I press "Add Location finder" in the "content_area"
    And I press "Save and publish"
    Then I should see "White village"
    And I should see "Branch One"
    And I should see "1234567890"
    And I should see "Main road 10"
    And I should see "98101 WA"


