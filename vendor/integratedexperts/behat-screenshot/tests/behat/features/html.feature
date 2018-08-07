Feature: HTML screenshots

  Ensure that screenshots for HTML-base driver can be captured.

  @phpserver
  Scenario: Capture a screenshot using HTML-based driver
    When I am on the screenshot test page
    And the response status code should be 200
    And I save screenshot
    Then file wildcard "*.html.feature_9\.html" should exist
