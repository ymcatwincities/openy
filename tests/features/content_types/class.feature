@openy @api @classtest
Feature: Class Content type
  As Admin I want to make sure that Class content type is available with needed fields.

  Background: Class test setup
    Given I am logged in as a user with the "Editor" role
    And I create large branch content:
      | KEY                                 | behat_branch    |
      | title                               | BEHAT BRANCH    |
      | field_location_address:country_code | US              |
      | :address_line1                      | Main road 10    |
      | :locality                           | Seattle         |
      | :administrative_area                | WA              |
      | :postal_code                        | 98101           |
      | field_location_coordinates:lat      | 47.293433       |
      | :lng                                | -122.238717     |
      | field_location_phone                | +1234567890     |
    And I create paragraph of type class_location:
      | KEY            |
      | class_location |
    And I create large class content:
      | KEY                   | behat_class    |
      | title                 | BEHAT CLASS    |
      | field_sidebar_content | class_location |

  Scenario: Create basic Class
    Given I go to "/node/add/class"
    And I fill in "Title" with "Class One"
    When I press "Save and publish"
    Then I should see the message "Class Class One has been created."

  Scenario: I see appropriate content on class
    Given I view node "behat_class"
    Then I should see "Class schedule for all locations"
    # Go to class with parameter location=%id_of_branch.
    Then I view node "behat_class" with query parameter "location" = id of "behat_branch"
    And I should see "BEHAT BRANCH"
    And I should see "Main road 10"
    And I should see "98101"
    And I should see "WA"
    And I should see "+1234567890"
