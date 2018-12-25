@openy @api
Feature: Camp menu on camp and landing page
  As an Admin I want to make sure the camp menu links display appropriately on a camp and related landing page.

  Background: Log in
    Given I am logged in as a user with the "Administrator" role

  # todo Refactor
  # Since no data persists between scenarios these steps had to be run under one.
  Scenario: Camp menu on camp and landing page
    # Create Landing page with camp menu
    Given I go to "/node/add/landing_page"
    And I fill in "Title" with "Landing page with camp"
    And I uncheck the box "Generate automatic URL alias"
    When I select "One Column" from "Layout"
    And I press "Save"
    Then I should see "Landing Page With Camp"
    Then I should see the message "Landing Page Landing page with camp has been created."
    And I store the Node as "landing-page-node"

    # Create Camp with camp menu
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
    And I press "Save"
    Then I should see the message "Camp Camp with camp menu has been created."
    And I should see "Landing page with camp link"
    And I store the Node as "camp-node"

    # Edit Landing page with camp to add a Camp menu
    Given I go to stored Node "edit_url" from "landing-page-node"
    And I press "Add Camp menu" in the "header_area"
    Then I press "Save"
    And I should see the message "Landing Page Landing page with camp has been updated."
    And I should see "Landing page with camp link"

    # Set homepage to Landing page with camp by alias
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with stored Node "path" from "landing-page-node"
    Then I press the "Save configuration" button
    # Because "I should see the message" was not working on this page.
    And I go to "/admin/config/system/site-information"
    And The "Default front page" field should contain stored Node "path" from "landing-page-node"
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

    # Edit Camp with camp menu to set Landing page with camp link to <front>
    Given I go to stored Node "edit_url" from "camp-node"
    And I fill in "URL" with "<front>"
    Then I press "Save"
    And I should see the message "Camp Camp with camp menu has been updated."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

    # Set homepage to Landing page with camp by system url
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with stored Node "system_url" from "landing-page-node"
    Then I press "Save configuration"
    # Because "I should see the message" was not working on this page.
    And I go to "/admin/config/system/site-information"
    And The "Default front page" field should contain stored Node "alias_url" from "landing-page-node"
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

    # Edit Camp with camp menu to set Landing page with camp link to it's system url
    Given I go to stored Node "edit_url" from "camp-node"
    And I fill in "URL" with stored Node "system_url" from "landing-page-node"
    Then I press "Save"
    And I should see the message "Camp Camp with camp menu has been updated."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

    # Add a new alias for Landing page with camp
    Given I go to "/admin/config/search/path/add"
    And I fill in "Existing system path" with stored Node "system_url" from "landing-page-node"
    And I fill in "Path alias" with "/landing-page-new-alias"
    Then I press "Save"
    # Because "I should see the message" was not working on this page.
    And I go to "/admin/config/search/path"
    And I fill in "filter" with stored Node "system_url" from "landing-page-node"
    And I press the "Filter" button
    And I should not see "/landing-page-new-alias"
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "alias_url" from "landing-page-node"
    And I should see "Landing page with camp link"

    # Edit Camp with camp menu to set Landing page with camp link to it's new alias
    Given I go to stored Node "edit_url" from "camp-node"
    And I fill in "URL" with "/landing-page-new-alias"
    Then I press "Save"
    And I should see the message "Camp Camp with camp menu has been updated."
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to "/landing-page-new-alias"
    And I should see "Landing page with camp link"

    # Set homepage back to /node/15 (Open Y landing page)
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with node path of "Open Y"
    Then I press the "Save configuration" button
    # Because "I should see the message" was not working on this page.
    And I go to "/admin/config/system/site-information"
    And the "Default front page" field should contain node path of "Open Y"
    Then I go to "/"
    And I should get a 200 HTTP response
