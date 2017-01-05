Feature: Membership page
  @fast-tests @headless
  Scenario: Submit the form
    Given I go to "membership"
    When I fill in "First Name" with "Alex"
      And I fill in "Last Name" with "Test"
      And I fill in "Email Address" with "alex.for.ffw@gmail.com"
      And I fill in "Phone number" with "1234567890"
      And I select "Andover" from "What is your preferred Y Location?"
      And I click on "#edit-submit"
    Then I should see "Thank You"
      
      
  
