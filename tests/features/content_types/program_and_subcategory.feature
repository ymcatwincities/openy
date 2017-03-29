@openy @api
Feature: Program and Subcategory pages
  As Admin I want to make sure that Program and Subcategory can be created.
  And I should see paragraph with Subcategory teaser on Program page.

  Background: Create necessary content for tests
    Given I create taxonomy_term of type color:
      | KEY     | name          | field_color |
      | magenta | Behat Magenta | FF00FF      |

  Scenario: Create basic program and subcategory and check fields
    Given I am logged in as a user with the "Editor" role
    And I create a color term
    When I go to "/node/add/program"
    And I fill in "Title" with "Behat Fitness"
    And I select "Behat Magenta" from "Color"
    And I fill in the following:
      | Description | Program suggests fitness classes for all ages. |
    And I press "Add Categories Listing" in the "content_area"
    When I press "Save and publish"
    Then I should see the message "Program Behat Fitness has been created."

    Given I am logged in as a user with the "Editor" role
    When I go to "/node/add/program_subcategory"
    And I fill in "Title" with "Behat Personal Training"
    And I fill in "Program" with "Behat Fitness"
    And I select "Behat Magenta" from "Color"
    And I fill media field "edit-field-category-image-target-id" with "media:1"
    And I fill in the following:
      | Description | Program suggests fitness classes for all ages. |
    When I press "Save and publish"
    Then I should see the message "Program Subcategory Behat Personal Training has been created."

    When I go to "/programs/behat-fitness"
    Then I see the heading "Behat Fitness"
    And I should see the heading "Behat Personal Training"
    And I should see a ".subprogram-listing-item img" element
    And I should see "Program suggests fitness classes for all ages."
    And I should see the link "Open category"

