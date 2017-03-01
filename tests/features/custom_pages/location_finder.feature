@openy @api
Feature: Location finder
  I want to make sure that Location Finder Page shows new branches. Given some content exists.

  @javascript
  Scenario: Make sure map element is present
    When I go to "/locations"
    Then I should see a ".map" element
    And I should not see a ".gm-err-message" element

  @javascript
  Scenario: Check locations displayed by default
    When I go to "/locations"
    And I should see "Downtown YMCA"
    And I should see "East YMCA"
    And I should see "South YMCA"
    And I should see "West YMCA"

  @javascript
  Scenario: Check camps displayed based on filters
    When I go to "/locations"
    And I check "Camps"
    And I uncheck "YMCA"
    Then I should see "Camp Colman"
    And I should see "Camp Orkila"
    And I should see "Camp Terry"

  Scenario: Check distance filters are available
    When I go to "/locations"
    And I should see a "input[placeholder^='Enter ZIP']" element
    And I should see a "select.distance_limit" element

  @javascript
  Scenario: Check unpublished
    Given "branch" content:
      |title         | status |
      |Hidden branch |   0    |
    When I go to "/locations"
    Then I should not see "Hidden branch"

  @javascript
  Scenario: Check "Coming soon" branch
    Given "branch" content:
      | title      | field_location_state |
      | New branch | 1                    |
    When I go to "/locations"
    Then I should see "coming soon!"

  @javascript
  Scenario: Check facilities
    Given "facility" content:
      | title |
      | Facility One |
    When I go to "/locations"
    Then I should see "Facility One"