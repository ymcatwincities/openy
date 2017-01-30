@openy @api
Feature: Program Content type
  As Admin I want to make sure that Program content type is OK

  Scenario: Create basic program and check fields
    Given I am logged in as a user with the "Administrator" role
    And I create a color term
    When I go to "/node/add/program"
    And I fill in "Title" with "Fitness"
    And I select "Magenta" from "Color"
    And I fill in the following:
      | Description | Program suggests fitness classes for all ages. |
    When I press "Save and publish"
    Then I should see the message "Program Fitness has been created."

  #Scenario: Create subcategory
    Given I am logged in as a user with the "Administrator" role
    When I go to "/node/add/program_subcategory"
    And I fill in "Title" with "Personal Training"
    And I fill in "Program" with "Fitness"
    And I select "Magenta" from "Color"
    And I fill in the following:
      | Description | Program suggests fitness classes for all ages. |
    When I press "Save and publish"
    Then I should see the message "Program Subcategory Personal Training has been created."

