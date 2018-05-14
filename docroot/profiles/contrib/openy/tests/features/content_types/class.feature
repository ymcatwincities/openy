@openy @api @classtest
Feature: Class Content type
  As Editor I want to make sure that Class content type is available with needed fields.

  Background: Class test setup
    Given I am logged in as a user with the "Editor" role
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
      | KEY                    | behat_category |
      | title                  | BEHAT CATEGORY |
      | field_category_program | behat_program  |
    And I create large activity content:
      | KEY                     | behat_activity |
      | title                   | BEHAT ACTIVITY |
      | field_activity_category | behat_category |
    And I create large branch content:
      | KEY                                 | behat_branch    |
      | title                               | BEHAT BRANCH    |
      | field_location_address:country_code | US              |
      | :address_line1                      | Main road 10    |
      | :locality                           | Seattle         |
      | :administrative_area                | WA              |
      | :postal_code                        | 98101           |
      | field_location_coordinates:lat      | 47.293433       |
      | :lng                                | -122.238717     |
      | field_location_phone                | +1234567890     |
    And I create paragraph of type class_location:
      | KEY            |
      | class_location |
    And I create paragraph of type class_sessions:
      | KEY               |
      | class_sessions_01 |
      | class_sessions_02 |
      | class_sessions_03 |
    And I create large class content:
      | KEY                   | behat_class_01    |
      | title                 | BEHAT CLASS       |
      | field_class_activity  | behat_activity    |
      | field_content         | class_sessions_01 |
      | field_sidebar_content | class_location    |
    And I create large class content:
      | KEY                   | behat_class_02    | behat_class_03    |
      | title                 | BEHAT CLASS       | BEHAT CLASS       |
      | field_class_activity  | behat_activity    | behat_activity    |
      | field_content         | class_sessions_02 | class_sessions_03 |
    And I create large paragraph of type session_time:
      | KEY                           | session_time_01     | session_time_02     |
      | field_session_time_date:value | 2037-04-20T12:00:00 | 2037-04-20T12:00:00 |
      | :end_value                    | 2037-04-20T13:00:00 | 2037-04-20T13:00:00 |
      | field_session_time_days       | monday              | monday              |
    Then I create large session content:
      | KEY                          | behat_session_01        |
      | title                        | BEHAT SESSION           |
      | field_session_class          | behat_class_02          |
      | field_session_location       | behat_branch            |
      | field_session_time           | session_time_01         |
      | field_session_online         | 1                       |
      | field_session_ticket         | 0                       |
      | field_session_in_mbrsh       | 0                       |
      | field_session_min_age        | 5                       |
      | field_session_max_age        | 101                     |
      | field_session_reg_link:uri   | http://www.openymca.org |
      | :title                       | Register Now            |
    Then I create large session content:
      | KEY                          | behat_session_02        |
      | title                        | BEHAT SESSION           |
      | field_session_class          | behat_class_03          |
      | field_session_location       | behat_branch            |
      | field_session_time           | session_time_02         |
      | field_session_online         | 0                       |
      | field_session_ticket         | 1                       |
      | field_session_in_mbrsh       | 1                       |

  Scenario: Create basic Class
    Given I go to "/node/add/class"
    And I fill in "Title" with "Class One"
    When I press "Save"
    Then I should see the message "Class Class One has been created."

  Scenario: I see appropriate branch content on class
    Given I view node "behat_class_01"
    Then I should see "Class schedule for all locations"
    # Go to class with parameter location=%id_of_branch.
    Then I view node "behat_class_01" with query parameter "location" = id of "behat_branch"
    And I should see "BEHAT BRANCH"
    And I should see "Main road 10"
    And I should see "98101"
    And I should see "WA"
    And I should see "+1234567890"

  Scenario: I see appropriate sessions content on class
    Given I view node "behat_class_02"
    Then Element ".class-page-other-sessions.row .location" has text "BEHAT BRANCH"
    And Element ".class-page-other-sessions.row .days" has text "Monday, 12:00PM - 1:00PM, Apr 20, 2037"
    And Element ".class-page-other-sessions.row .age_range" has text "Ages 5 - 101"
    And Element ".class-page-other-sessions.row .details .registration-online" has text "Online registration"
    And I should not see "Ticket required"
    And I should not see "Included in membership"
    And Element ".class-page-other-sessions.row .registration_link a" has text "Register Now"

  @testmenow
  Scenario: I see appropriate sessions content on class
    Given I view node "behat_class_03"
    Then Element ".class-page-other-sessions.row .location" has text "BEHAT BRANCH"
    And Element ".class-page-other-sessions.row .days" has text "Monday, 12:00PM - 1:00PM, Apr 20, 2037"
    And the ".class-page-other-sessions.row .age_range" element should not contain "Ages"
    And I should not see "Online registration"
    And Element ".class-page-other-sessions.row .details .ticket-required" has text "Ticket required"
    And Element ".class-page-other-sessions.row .details .in-membership" has text "Included in membership"
    And I should not see "Register Now"
