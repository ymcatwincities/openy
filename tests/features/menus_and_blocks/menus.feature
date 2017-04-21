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

  Scenario: Check menu items are in the appropriate place
    Given I am an anonymous user
    And I am on the homepage
    Then I should see "BEHAT GIVE" in the "header"
    And I should see "BEHAT PROGRAMS" in the "header"
    And I should see an "ul.nav.dropdown-menu .menu-item-behat-childcare" element
    And Element "ul.nav.dropdown-menu .menu-item-behat-childcare" has text "BEHAT CHILDCARE"
    And Element "ul.nav.dropdown-menu .menu-item-behat-childcare" has text "BEHAT DAYCARE"
    And I should see "BEHAT LOCATIONS" in the "footer"
    And I should see "BEHAT JOIN" in the "footer"
    And I should see "BEHAT CAREERS" in the "footer"

  Scenario: Create footer menu left item and check
    Given I am logged in as a user with the Editor role
    And I go to "/admin/structure/menu/manage/main/add"
    And I fill in "Menu link title" with "BEHAT EDITOR ADD"
    And I fill in "Link" with "http://openymca.org/"
    And I check the box "Show as expanded"
    And I attach the file "childcare.png" to "Icon image"
    And I press "Save"
    When I am an anonymous user
    And I go to homepage
    Then I should see "BEHAT EDITOR ADD" in the "header"

  Scenario: Remove test menu item
    Given I am logged in as a user with the "Administrator" role
    And I go to "/admin/structure/menu/manage/main"
    And I click "tr:contains('BEHAT EDITOR ADD') td a:contains('Delete')" element
    And I press the "Delete" button
