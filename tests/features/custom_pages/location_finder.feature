@openy @api @locationfinder
Feature: Location finder
  I want to make sure that Location Finder Page shows new branches. Given some content exists.

  Background: Create necessary content for tests
    Given I create branch content:
      | title                     | field_location_state | status |
      | Behat Branch Comings Soon | 1                    | 1      |
      | Behat Branch Normal       | 0                    | 1      |
      | Behat Branch Unpublished  | 0                    | 0      |
    And I create large camp content:
      | title                               | Behat Camp 01 | Behat Camp 02 | Behat Camp Unpublished |
      | status                              | 1             | 1             | 0                      |
      | field_camp_menu_links:uri           | internal:/    | internal:/    | internal:/             |
      | :title                              | homepage      | homepage      | homepage               |
      | field_location_address:country_code | US            | US            | US                     |
      | :address_line1                      | Main road 10  | Main road 10  | Main road 10           |
      | :locality                           | Seattle       | Seattle       | Seattle                |
      | :administrative_area                | WA            | WA            | WA                     |
      | :postal_code                        | 98101         | 98101         | 98101                  |
      | field_location_coordinates:lat      | 47.293433     | 47.293433     | 47.293433              |
      | :lng                                | -122.238717   | -122.238717   | -122.238717            |
      | field_location_phone                | +1234567890   | +1234567890   | +1234567890            |
    And I create large facility content:
      | title                               | Behat Facility 01 | Behat Facility 02 | Behat Facility Unpublished |
      | status                              | 1                 | 1                 | 0                          |
      | field_camp_menu_links:uri           | internal:/        | internal:/        | internal:/                 |
      | :title                              | homepage          | homepage          | homepage                   |
      | field_location_address:country_code | US                | US                | US                         |
      | :address_line1                      | Main road 10      | Main road 10      | Main road 10               |
      | :locality                           | Seattle           | Seattle           | Seattle                    |
      | :administrative_area                | WA                | WA                | WA                         |
      | :postal_code                        | 98101             | 98101             | 98101                      |
      | field_location_coordinates:lat      | 47.293433         | 47.293433         | 47.293433                  |
      | :lng                                | -122.238717       | -122.238717       | -122.238717                |
      | field_location_phone                | +1234567890       | +1234567890       | +1234567890                |
    Then I create paragraph of type prgf_location_finder_filters:
      | KEY            |
      | finder_filters |
    And I create paragraph of type prgf_location_finder:
      | KEY             |
      | location_finder |
    And I create landing_page content:
      | KEY             | title           | field_header_content | field_content   |
      | behat_locations | Behat Locations | finder_filters       | location_finder |

  @javascript
  Scenario: Make sure map element is present
    When I go to "/behat-locations"
    Then I should see a ".map" element
    And I should not see a ".gm-err-message" element

  @javascript
  Scenario: Check distance filters are available
    When I go to "/behat-locations"
    And I should see a "input[placeholder^='Enter ZIP']" element
    And I should see a "select.distance_limit" element
    And I should see a ".tag_filters input.tag_YMCA" element
    And I should see a ".tag_filters input.tag_Camps" element
    And I should see a ".tag_filters input.tag_Facilities" element

  @javascript
  Scenario: Check locations displayed by default
    When I go to "/behat-locations"
    And I should see "Behat Branch Comings Soon"
    And I should see "coming soon!"
    And I should see "Behat Branch Normal"
    And I should not see "Behat Branch Unpublished"

  @javascript
  Scenario: Check camps displayed based on filters
    When I go to "/behat-locations"
    And I check "Camps"
    And I uncheck "YMCA"
    Then I should see "Behat Camp 01"
    And I should see "Behat Camp 02"
    And I should not see "Behat Camp Unpublished"

  @javascript
  Scenario: Check facilities displayed based on filters
    When I go to "/behat-locations"
    And I check "Facilities"
    And I uncheck "YMCA"
    Then I should see "Behat Facility 01"
    And I should see "Behat Facility 02"
    And I should not see "Behat Facility Unpublished"
