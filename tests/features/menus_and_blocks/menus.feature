Feature: Menus
  Check that menus items are available and displayed in right regions

  Scenario: Create main menu item and check
    Given I am logged in as a user with the "Administrator" role
    And I create an item "Give" in the "main" menu
    When I go to homepage
    Then I should see "Give" in the "header"

  Scenario: Create menu item with icon
    Given I am logged in as a user with the "Administrator" role
    And I create an item "Programs" in the "main" menu
    And I go to "/admin/structure/menu/manage/main/add"
    And I fill in the following:
      | Menu link title | Fitness   |
      | Link            | /fitness  |
      | Parent link     | Programs  |
    And I attach the file "fitness.svg" to "Icon image"


  Scenario: Create footer menu left item and check
    Given I am logged in as a user with the "Administrator" role
    And I create an item "TBD" in the "footer-menu-left" menu
    When I go to homepage
    Then I should see "TBD" in the "footer"

  Scenario: Create footer menu right item and check
    Given I am logged in as a user with the "Administrator" role
    And I create an item "TBD" in the "footer-menu-center" menu
    When I go to homepage
    Then I should see "TBD" in the "footer"

  Scenario: Create footer menu center item and check
    Given I am logged in as a user with the "Administrator" role
    And I create an item "TBD" in the "footer-menu-center" menu
    When I go to homepage
    Then I should see "TBD" in the "footer"