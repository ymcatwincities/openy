@openy @api @javascript
Feature: GroupEx Pro paragraphs

  Background: GroupEx Pro test setup
    Given I am logged in as a user with the "Administrator" role
    Then I go to "/admin/config/services/openy_group_schedules/settings"
    And I fill in "GroupEx Account ID" with "36"
    Then I press the "Save configuration" button
    Then I go to "/admin/config/services/groupexpro"
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
    When I select "202" from "location"
    Then I wait for AJAX to finish
    And I select this "next monday" from "date_select"
    Then I wait for AJAX to finish
    And I should see an ".groupex-pdf-link-container a:contains('Download PDF')" element
    And I should see text matching "8:30am 60 min"
    And I should see text matching "Chisel Studio 1"
    And I should see text matching "Jennifer K."
    And I should see text matching "Chisel Visit Strength with Jennifer K. Class will take place at Studio 1. GXP Club - Fable Jennifer K."
    And I should see text matching "11:00am 60 min"
    And I should see text matching "Yoga GX2"
    And I should see text matching "John F."
    And I should see text matching "Yoga Visit Yoga with John F. Class will take place at GX2. GXP Club - Fable John F."
    And I should see text matching "7:00pm 60 min"
    And I should see text matching "3D XTREME­&trade; GX1"
    And I should see text matching "Melissa T."
    And I should see text matching "3D XTREME­&trade; Visit Cardio with Melissa T. Class will take place at GX1. GXP Club - Fable Melissa T."
    Then I click "a:contains('Jennifer K.')" element
    Then I wait for AJAX to finish
    And I should see an ".groupex-pdf-link-container a:contains('Download PDF')" element
    And I should see text matching "9:00am 60 min"
    And I should see text matching "3D XTREME­&trade; GX1"
    And I should see text matching "Jennifer K."
    And I should see text matching "3D XTREME­&trade; Visit Combo Cardio/Strength with Jennifer K. Class will take place at GX1. GXP Club - Fable Jennifer K."
    And I should see text matching "8:30am 60 min"
    And I should see text matching "Chisel"
    And I should see text matching "Studio 1"
    And I should see text matching "Jennifer K."
    And I should see text matching "Chisel Visit Strength with Jennifer K. Class will take place at Studio 1. GXP Club - Fable Jennifer K."
    And I should see text matching "8:30am 60 min"
    And I should see text matching "Chisel"
    And I should see text matching "Studio 1"
    And I should see text matching "Jennifer K."
    And I should see text matching "Chisel Visit Strength with Jennifer K. Class will take place at Studio 1. GXP Club - Fable Jennifer K."
    And I should see text matching "1:30pm 60 min"
    And I should see text matching "Barre Above"
    And I should see text matching "Jennifer K."
    And I should see text matching "Barre Above Visit Barre with Jennifer K. Class will take place at . GXP Club - Fable Jennifer K."
    And I should see text matching "7:00am 60 min"
    And I should see text matching "U-Jam Fitness®"
    And I should see text matching "GX1"
    And I should see text matching "Jennifer K."
    And I should see text matching "U-Jam Fitness® Visit Dance with Jennifer K. Class will take place at GX1. GXP Club - Fable Jennifer K."
    Then I click "a:contains('Chisel')" element
    Then I wait for AJAX to finish
    And I should see an ".groupex-pdf-link-container a:contains('Download PDF')" element
    And I should see text matching "A full body strength workout. Using weights, tubing, body bars and more!  For all levels."
    And I should see text matching "Chisel Visit Strength with Jennifer K. Class will take place at Studio 1. GXP Club - Fable Jennifer K."

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
    And I should see text matching "5:00am-9:00am"
    And I should see text matching "Open Swim"
    And I should see text matching "John F."
    And I should see text matching "Open Swim"
    And I should see text matching "GXP Club - Reilly"
    Then I select "5506" from "categories"
    Then I wait for AJAX to finish
    And I should see text matching "5:00am-9:00am"
    And I should see text matching "Open Swim"
    And I should see text matching "John F."
    And I should see text matching "Open Swim"
    And I should see text matching "GXP Club - Reilly"
