@openy @api
Feature: Blog Content type
  As Admin I want to make sure that Blog content type is OK

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario: Create basic blog and check fields
    Given I am logged in as a user with the Administrator role
    And I create a branch
    And I create a "Category One" term in the "Blog Categories" taxonomy
    When I go to "/node/add/blog"
    And I fill in "Title" with "From OpenY Automation Blogger"
    And I select "Baytown" from "Belongs to content"
    And I fill in the following:
      | Category | Category One |
#      | Description | This could be a draft for a wonderful post. |
    When I press "Save and publish"
    Then I should see the message "Blog From OpenY Automation Blogger has been created."
