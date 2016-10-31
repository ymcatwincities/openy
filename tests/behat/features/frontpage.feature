Feature: Frontpage feature
  @fast-tests @headless
  Scenario: Check frontpage is accessible
    Given I am on homepage
    When I should see "YMCA Twin Cities"
