@openy @api
Feature: Menus
  Check that menus items are available and displayed in right regions

  Scenario: Create main menu item and check
    Given I am logged in as a user with the "Editor" role
    And I create an item "Give" in the "main" menu
    When I am an anonymous user
    And I go to homepage
    Then I should see "Give" in the "header"

  Scenario: Create menu item for second level
    Given I am logged in as a user with the "Editor" role
    And I create an item "Programs" in the "main" menu
    And I go to "/admin/structure/menu/manage/main/add"
    And I fill in the following:
      | Menu link title | Childcare   |
      | Link            | /childcare  |
    And I select "-- Programs" from "Parent link"
    And I attach the file "childcare.png" to "Icon image"
    And I press "Save"
    When I am an anonymous user
    And I go to the homepage
    Then I should see "Childcare" in the "dropdown_menu"
    
  Scenario: Create footer menu left item and check
    Given I am logged in as a user with the "Editor" role
    And I create an item "LOCATIONS" in the "footer-menu-left" menu
    When I am an anonymous user
    And I go to homepage
    Then I should see "LOCATIONS" in the "footer"

  Scenario: Create footer menu right item and check
    Given I am logged in as a user with the "Editor" role
    And I create an item "JOIN" in the "footer-menu-right" menu
    When I am an anonymous user
    And I go to homepage
    Then I should see "JOIN" in the "footer"

  Scenario: Create footer menu center item and check
    Given I am logged in as a user with the "Editor" role
    And I create an item "CAREERS" in the "footer-menu-center" menu
    When I am an anonymous user
    And I go to homepage
    Then I should see "CAREERS" in the "footer"