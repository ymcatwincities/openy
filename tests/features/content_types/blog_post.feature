@openy @api
Feature: Blog Content type
  As Admin I want to make sure that Blog content type is OK

  Scenario: Create basic blog and check fields
    Given I run drush mi "--all"
    And I am logged in as a user with the "Administrator" role
    And I create a branch
    When I go to "/node/add/blog"
    And I fill in "Title" with "From OpenY Automation Blogger"
    And I select "Test Branch" from "Location"
    And I fill in the following:
      | Category | Volunteer |
      | Description | This could be a draft for a wonderful post. |
    When I press "Save and publish"
    Then I should see the message "Blog Post From OpenY Automation Blogger has been created."
