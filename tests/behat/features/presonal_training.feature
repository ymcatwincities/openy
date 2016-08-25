Feature: Personal Training Form
  @javascript
  Scenario: Check Personal Training form requests and results
    Given I visit "/health__fitness/personal_training/personal-trainer-schedules"
    When I click on ".js-form-item-mb-location"
    And I wait AJAX
    Then I should see "Appointment Type" in the "#program-wrapper" element
    When I click on ".js-form-item-mb-program"
    And I wait AJAX
    Then I should see "Training type" in the "#session-type-wrapper" element
    When I click on ".js-form-item-mb-session-type"
    And I wait AJAX
    Then I should see "Trainer" in the "#trainer-wrapper" element
    When I click on ".form_submit"
    And I wait AJAX
    Then I should see "Showing results for" in the ".mindbody-search-results-header" element
