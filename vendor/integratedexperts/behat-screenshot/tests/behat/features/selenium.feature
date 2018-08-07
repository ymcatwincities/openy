Feature: Selenium screenshots

  Ensure that screenshots for Selenium driver can be captured.

  @phpserver @javascript
  Scenario: Capture a screenshot using Selenium driver
    When I am on the screenshot test page
    And save screenshot
    Then file wildcard "*.selenium.feature_8.png" should exist
