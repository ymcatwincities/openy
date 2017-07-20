@openy @api @javascript
Feature: Microsites menu paragraphs
  Check that Microsites menu paragraph output is working

  Background: Create necessary content for tests
    Given I am logged in as a user with the "Editor" role
    And I create custom block of type menu block:
      | KEY                                | behat_menu_block_01
      | info:value                         | BEHAT MENU BLOCK
      | field_menu_block_color             | White
      | field_menu_block_text_color:value1 | Blue
      | :value2                            | Blue
      | field_menu_block_links:uri         | http://openymca.org
      | :title                             | About OpenY
    And I create large paragraph of type microsites menu:
      | KEY                          | behat_microsites_menu
      | field_prgf_block_ref         | behat_menu_block_01
    And I create large landing_page content:
      | KEY                          | behat_landing_1
      | title                        | BEHAT LANDING 1
      | field_header_content         | behat_microsites_menu

  Scenario: Create basic landing page and check fields
    Given I go to "/node/add/landing_page"
    And I fill in "Title" with "Basic Landing"
    And I select "One Column" from "Layout"
    Then I should see "Header Area"
    And I should see "Content Area"
    And I should see "Sidebar Area"
    And I should see "Bottom Area"
    When I press "Save and publish"
    Then I should see the message "Landing Page Basic Landing has been created."

  Scenario: Microsites menu present appropriate data on landing page
    Given I view node "behat_landing_1"
    And I should see "About OpenY"
    And I should see an ".microsites-menu__wrapper" element
