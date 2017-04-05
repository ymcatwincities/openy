@openy @api
Feature: Menus
  Check that menus items are available and displayed in right regions

  Background: Create menu items for test
    Given I create menu_link_content:
      | menu_name          | title           | uri                                               | expanded |
      | main               | BEHAT PROGRAMS  | base:behat-programs                               | 1        |
      | main               | BEHAT GIVE      | base:behat-give                                   | 1        |
      | footer-menu-left   | BEHAT LOCATIONS | base:behat-locations                              | 1        |
      | footer-menu-right  | BEHAT JOIN      | base:behat-join                                   | 1        |
      | footer-menu-center | BEHAT CAREERS   | base:behat-careers                                | 1        |
    And I create menu_link_content:
      | menu_name          | title           | uri                                               | expanded | parent_uri                          | parent_title    | icon_image    |
      | main               | BEHAT CHILDCARE | base:behat-programs/behat-childcare               | 1        | base:behat-programs                 | BEHAT PROGRAMS  | childcare.png |
      | main               | BEHAT DAYCARE   | base:behat-programs/behat-childcare/behat-daycare | 1        | base:behat-programs/behat-childcare | BEHAT CHILDCARE |               |
    Then I am an anonymous user

  Scenario: Check menu items are in the appropriate place
    Given I am on the homepage
    Then I should see "BEHAT GIVE" in the "header"
    And I should see "BEHAT PROGRAMS" in the "header"
    And I should see an "ul.nav.dropdown-menu .menu-item-behat-childcare" element
    And Element "ul.nav.dropdown-menu .menu-item-behat-childcare" has text "BEHAT CHILDCARE"
    And I should see an "ul.nav.dropdown-menu .menu-item-behat-childcare .section-icon" element
    And Element "ul.nav.dropdown-menu .menu-item-behat-childcare" has text "BEHAT DAYCARE"
    And I should see "BEHAT LOCATIONS" in the "footer"
    And I should see "BEHAT JOIN" in the "footer"
    And I should see "BEHAT CAREERS" in the "footer"
