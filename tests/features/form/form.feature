@openy @api
Feature: Web forms
  As Admin I submit the form and ensure I got my submission in results.

  Scenario: Check form forms submission
    Given I am logged in as a user with the "Administrator" role
    When I go to "form/contact"
    And I fill in "Subject" with "First submission"
    And I fill in "Message" with "Actual message for the first submission"
    And I press "Send message"
    Then I should see "Your message has been sent"
    And I go to "admin/structure/webform/manage/contact/results/submissions"
    And I should see "First submission"
    And I should see "Actual message for the first submission"

