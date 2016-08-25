Feature: Frontpage feature
  @javascript
  Scenario: Check frontpage is accessible
    Given I am on homepage
    When I should see "YMCA Twin Cities"
