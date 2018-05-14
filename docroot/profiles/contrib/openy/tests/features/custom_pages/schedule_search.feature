@openy @api @javascript @schedulesearch
Feature: Schedule search
  Validate proper session and session instance

  Background: Create necessary content for tests
    Given I create large branch content:
      | KEY   | behat_branch |
      | title | BEHAT BRANCH |
    And I create taxonomy_term of type color:
      | KEY     | name          | field_color |
      | magenta | Behat Magenta | FF00FF      |
    And I create large program content:
      | KEY                 | behat_program |
      | title               | BEHAT PROGRAM |
      | field_program_color | magenta       |
    And I create large program_subcategory content:
      | KEY                    | behat_category |
      | title                  | BEHAT CATEGORY |
      | field_category_program | behat_program  |
    And I create large activity content:
      | KEY                     | behat_activity |
      | title                   | BEHAT ACTIVITY |
      | field_activity_category | behat_category |
    And I create large class content:
      | KEY                           | behat_class              |
      | title                         | BEHAT CLASS              |
      | field_class_activity          | behat_activity           |
      | field_class_description:value | BEHAT Class Description. |
      | :format                       | full_html                |
    And I create large paragraph of type session_time:
      | KEY                           | session_time_01            |
      | field_session_time_date:value | 2037-04-20T12:00:00        |
      | :end_value                    | 2037-04-22T13:00:00        |
      | field_session_time_days       | monday, tuesday, wednesday |
    Then I create large session content:
      | KEY                    | behat_session   |
      | title                  | BEHAT SESSION   |
      | field_session_class    | behat_class     |
      | field_session_location | behat_branch    |
      | field_session_time     | session_time_01 |
    Then I create paragraph of type schedule_search_form:
      | KEY                  |
      | schedule_search_form |
    Then I create paragraph of type schedule_search_list:
      | KEY                  |
      | schedule_search_list |
    And I create large landing_page content:
      | KEY           | behat_schedule                             |
      | title         | BEHAT Schedule                             |
      | field_content | schedule_search_form, schedule_search_list |

  Scenario:
    Given I go to "/behat-schedule?date=04/20/2037"
    And I wait for AJAX to finish
    Then I should see "Classes for April 20, 2037"
    And I scroll to ".form-item-location" element
    And I select "BEHAT BRANCH" from "location"
    And I wait for AJAX to finish
    And I should see "12:00 pm"
    And I should see "BEHAT CLASS"
    And I should see "BEHAT Class Description."
