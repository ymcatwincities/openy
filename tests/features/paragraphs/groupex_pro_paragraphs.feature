@openy @api @javascript
Feature: GroupEx Pro paragraphs

  Background: GroupEx Pro test setup
    Given I am logged in as a user with the "Administrator" role
    Then I go to "/admin/openy/integration/groupex-pro/group_schedules_settings"
    And I fill in "GroupEx Account ID" with "36"
    Then I press the "Save configuration" button
    Then I go to "/admin/openy/integration/groupex-pro/groupexpro"
    And I fill in "GroupEx Pro Account" with "36"
    Then I press the "Save configuration" button
    Given I am logged in as a user with the "Editor" role
    And I create paragraph of type group_schedules:
      | KEY                |
      | group_schedules_01 |
    And I create paragraph of type embedded_groupexpro_schedule:
      | KEY                |
      | embedded_groupexpro_schedule_01 |
    And I create large landing_page content:
      | KEY             | behat_group_schedules | behat_embedded_groupexpro_schedules |
      | title           | BEHAT GROUP SCHEDULES | BEHAT EMBEDDED GROUPEX SCHEDULES    |
      | field_lp_layout | one_column            | one_column                          |
      | field_content   | group_schedules_01    | embedded_groupexpro_schedule_01     |

  Scenario: Verify Group Schedules is working.
    Given I view node "behat_group_schedules"
    Then I wait for AJAX to finish
    And I should see an "#groupex-full-form-wrapper" element
    And I should see an "#groupex-form-full" element
    And I should see an "#edit-location--wrapper" element
    And Element "#edit-location--wrapper .fieldset-legend" has text "Locations"
    And I should see an "#edit-location" element
    And I should see an "#edit-location .form-item-location" element

  Scenario: Verify Embedded GroupEx Schedules is working.
    Given I view node "behat_embedded_groupexpro_schedules"
    Then I wait for AJAX to finish
    And I should see an "#scheduleGXP" element
    And I should see an "#categoriesGXP" element
    And I should see an "#locationsGXP" element
    And I should see an "#classSearch" element
    And I should see an "#instructorSearch" element
    And I should see an "#GXPSunday" element
    And I should see an "#GXPMonday" element
    And I should see an "#GXPTuesday" element
    And I should see an "#GXPWednesday" element
    And I should see an "#GXPThursday" element
    And I should see an "#GXPFriday" element
    And I should see an "#GXPSaturday" element
