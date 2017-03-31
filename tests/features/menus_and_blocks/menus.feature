@openy @api @menutest
Feature: Menus
  Check that menus items are available and displayed in right regions

  Background: Create menu items for test
    Given I am logged in as a user with the "Editor" role
    And I create menu_link_content:
      | menu_name          | title           | uri                                 | expanded | parent_uri          | parent_title   |
      | main               | BEHAT PROGRAMS  | base:behat-programs                 | 1        |                     |                |
      | main               | BEHAT CHILDCARE | base:behat-programs/behat-childcare | 1        | base:behat-programs | BEHAT PROGRAMS |
      | main               | BEHAT GIVE      | base:behat-give                     | 1        |                     |                |
      | footer-menu-left   | BEHAT LOCATIONS | base:behat-locations                | 1        |                     |                |
      | footer-menu-right  | BEHAT JOIN      | base:behat-join                     | 1        |                     |                |
      | footer-menu-center | BEHAT CAREERS   | base:behat-careers                  | 1        |                     |                |
    Then I am an anonymous user

  Scenario: Check menu items are in the appropriate place
    Given I am on the homepage
    And I wait 15 seconds
    And I should see "BEHAT GIVE" in the "header"
    And I should see "BEHAT PROGRAMS" in the "header"
    And I should see "BEHAT CHILDCARE" in the "dropdown_menu"
    And I should see "BEHAT LOCATIONS" in the "footer"
    And I should see "BEHAT JOIN" in the "footer"
    And I should see "BEHAT CAREERS" in the "footer"
