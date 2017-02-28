@openy @api
Feature: Camp menu on camp and landing page
  As an Admin I want to make sure the camp menu links display appropriately on a camp and related landing page.

  Background: Log in
    Given I am logged in as a user with the "Administrator" role

  Scenario: Create Landing page with camp menu
    Given I go to "/node/add/landing_page"
    And I fill in "Title" with "Landing page with camp"
    When I select "One Column" from "Layout"
    And I press "Save and publish"
    Then I should see "Landing Page With Camp"
    Then I should see the message "Landing page Landing Page With Camp has been created."
    And I store the Node as "landing-page-node"

  Scenario: Create Camp with camp menu
    Given I go to "/node/add/camp"
    And I fill in "Title" with "Camp with camp menu"
    And I fill in "URL" with stored Node "reference_fill" from "landing-page-node"
    And I fill in the following:
      | Link text | Landing page with camp link |
    And I fill in the following:
      | Street address | 123 Test Road  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Phone          | +1234567890    |
    When I press "Add Camp menu" in the "header_area"
    And I press "Save and publish"
    Then I should see the message "Camp Camp with camp menu has been created."
    And I should see "Landing page with camp link"
    And I store the Node as "camp-node"

  Scenario: Edit Landing page with camp to add a Camp menu
    Given I go to stored Node "edit_url" from "landing-page-node"
    And I press "Add Camp menu" in the "header_area"
    Then I press "Save and publish"
    And I should see the message "Camp Camp with camp menu has been updated."
    And I should see "Landing page with camp link"

  Scenario: Set homepage to Landing page with camp by alias
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with stored Node "alias_url" from "landing-page-node"
    Then I press "Save configuration"
    And I should see the message "The configuration options have been saved."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

  Scenario: Edit Camp with camp menu to set Landing page with camp link to <front>
    Given I go to stored Node "edit_url" from "camp-node"
    And I fill in "URL" with "<front>"
    Then I press "Save and publish"
    And I should see the message "Camp Camp with camp menu has been updated."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

  Scenario: Set homepage to Landing page with camp by system url
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with stored Node "system_url" from "landing-page-node"
    Then I press "Save configuration"
    And I should see the message "The configuration options have been saved."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

  Scenario: Edit Camp with camp menu to set Landing page with camp link to it's system url
    Given I go to stored Node "edit_url" from "camp-node"
    And I fill in "URL" with stored Node "system_url" from "landing-page-node"
    Then I press "Save and publish"
    And I should see the message "Camp Camp with camp menu has been updated."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

  Scenario: Add a new alias for Landing page with camp
    Given I go to "/admin/config/search/path/add"
    And I fill in "Existing system path" with stored Node "system_url" from "landing-page-node"
    And I fill in "Path alias" with "/landing-page-new-alias"
    Then I press "Save"
    And I should see the message "The alias has been saved."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

  Scenario: Edit Camp with camp menu to set Landing page with camp link to it's new alias
    Given I go to stored Node "edit_url" from "camp-node"
    And I fill in "URL" with "/landing-page-new-alias"
    Then I press "Save and publish"
    And I should see the message "Camp Camp with camp menu has been updated."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to "/landing-page-new-alias"
    And I should see "Landing page with camp link"
