@openy @api @javascript @popuptest
Feature: Popups paragraphs
  As Editor I want to make sure that popup paragraphs work as expected

  Background: Popup test setup
    Given I am logged in as a user with the "Editor" role
    And I create paragraph of type branches_popup_all:
      | KEY                   |
      | branches_popup_all_01 |
      | branches_popup_all_02 |
    And I create large landing_page content:
      | KEY             | behat_landing         |
      | title           | BEHAT LANDING         |
      | field_lp_layout | one_column            |
      | field_content   | branches_popup_all_01 |
    And I create taxonomy_term of type color:
      | KEY     | name          | field_color |
      | magenta | Behat Magenta | FF00FF      |
    And I create large program content:
      | KEY                             | behat_program         |
      | title                           | BEHAT PROGRAM         |
      | field_program_color             | magenta               |
      | field_program_description:value | We rely on donations. |
      | :format                         | full_html             |
    And I create large program_subcategory content:
      | KEY                    | behat_category        |
      | title                  | BEHAT CATEGORY        |
      | field_category_program | behat_program         |
      | field_content          | branches_popup_all_02 |
    And I create large activity content:
      | KEY                     | behat_activity |
      | title                   | BEHAT ACTIVITY |
      | field_activity_category | behat_category |
    And I create large branch content:
      | KEY                                 | behat_branch_01 | behat_branch_02 | behat_branch_03 |
      | title                               | BEHAT BRANCH 01 | BEHAT BRANCH 02 | BEHAT BRANCH 03 |
      | field_location_address:country_code | US              | US              | US              |
      | :address_line1                      | Main road 10    | Main road 10    | Main road 10    |
      | :locality                           | Seattle         | Seattle         | Seattle         |
      | :administrative_area                | WA              | WA              | WA              |
      | :postal_code                        | 98101           | 98101           | 98101           |
      | field_location_coordinates:lat      | 47.293433       | 47.293433       | 47.293433       |
      | :lng                                | -122.238717     | -122.238717     | -122.238717     |
      | field_location_phone                | +1234567890     | +1234567890     | +1234567890     |
    And I create paragraph of type branches_popup_class:
      | KEY                  |
      | branches_popup_class |
    And I create large class content:
      | KEY                  | behat_class          |
      | title                | BEHAT CLASS          |
      | field_class_activity | behat_activity       |
      | field_content        | branches_popup_class |
    And I create large paragraph of type session_time:
      | KEY                           | session_time_01     | session_time_02     |
      | field_session_time_date:value | 2037-04-20T12:00:00 | 2037-04-20T12:00:00 |
      | :end_value                    | 2037-04-20T13:00:00 | 2037-04-20T13:00:00 |
      | field_session_time_days       | monday              | monday              |
    Then I create large session content:
      | KEY                    | behat_session_01 | behat_session_02 |
      | title                  | BEHAT SESSION 01 | BEHAT SESSION 02 |
      | field_session_class    | behat_class      | behat_class      |
      | field_session_location | behat_branch_01  | behat_branch_02  |
      | field_session_time     | session_time_01  | session_time_02  |

  Scenario: Branches popup (All) present appropriate data on program subcategory
    Given I view node "behat_category"
    Then I wait for AJAX to finish
    And Element ".openy-popups-branches-form .fieldset-legend" has text "Please select a location"
    And Element ".openy-popups-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 01"
    And Element ".openy-popups-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 02"
    And the ".openy-popups-branches-form .fieldset-wrapper" element should not contain "BEHAT BRANCH 03"
    And I should see an ".openy-popups-branches-form input.form-submit[value='Set location']" element

  Scenario: Branches popup (All) present appropriate data on landing page
    Given I view node "behat_landing"
    Then I wait for AJAX to finish
    And Element ".openy-popups-branches-form .fieldset-legend" has text "Please select a location"
    And Element ".openy-popups-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 01"
    And Element ".openy-popups-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 02"
    And Element ".openy-popups-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 03"
    And I should see an ".openy-popups-branches-form input.form-submit[value='Set location']" element

  Scenario: Branches popup (Class) presents appropriate data
    Given I view node "behat_class"
    Then I wait for AJAX to finish
    And Element ".openy-popups-class-branches-form .fieldset-legend" has text "Please select a location"
    And Element ".openy-popups-class-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 01"
    And Element ".openy-popups-class-branches-form .fieldset-wrapper" has text "BEHAT BRANCH 02"
    And I should see an ".openy-popups-class-branches-form input.form-submit[value='Set location']" element
