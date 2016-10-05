Feature: Personal Training Form
  @pt-form @javascript
  Scenario: Check Personal Training form requests and results
    Given I visit "/health__fitness/personal_training/personal-trainer-schedules"
    When I click on ".js-form-item-mb-location"
    And I wait for "#program-wrapper"
    Then I should see "Appointment Type" in the "#program-wrapper" element
    When I click on ".js-form-item-mb-program"
    And I wait for "#session-type-wrapper"
    Then I should see "Training type" in the "#session-type-wrapper" element
    When I click on ".js-form-item-mb-session-type"
    And I wait for "#trainer-wrapper"
    Then I should see "Trainer" in the "#trainer-wrapper" element
    When I click on ".form_submit"
    Then I should see "Showing results for" in the ".mindbody-search-results-header" element

  @pt-form @fast-tests @headless
  Scenario: Check data on PT results page
    Given I visit "/health__fitness/personal_training/personal-trainer-schedules/results?location=1&p=2&s=5&trainer=all&st=4&et=22&dr=3days"
    Then I should see "For appointment questions please call"
    And I should see "763-230-6537"
    And I should see "Book" in the ".mindbody-search-results-content"

  @pt-form @fast-test @headless @api
  Scenario: Check Book link permissions
    Given I am logged in as a user with the "book personal training time slots" permission
    Then I visit "/health__fitness/personal_training/personal-trainer-schedules/results?location=1&p=2&s=5&trainer=all&st=4&et=22&dr=3days"
    And I should see "Book" in the ".mindbody-search-results-content" element
