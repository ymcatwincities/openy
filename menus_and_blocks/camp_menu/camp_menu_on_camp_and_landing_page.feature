@openy @api
Feature: Camp menu on camp and landing page
  As an Admin I want to make sure the camp menu links display appropriately on a camp and related landing page.

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"
    Given I am logged in as a user with the "Administrator" role

  # Since no data persists between scenarios these steps had to be run under one.
  Scenario: Camp menu on camp and landing page
    # Create Landing page with camp menu
    Given I go to "/node/add/landing"
    And I fill in "Title" with "Landing page with camp"
    Then I should see "Landing Header"
    And I press "Add Camp menu"
    And I press "Save and publish"
    Then I should see "Landing page with camp"
    And I should see " has been created."
    And I store the Node as "landing-node"

    # Create Landing page with camp menu and alias
    Given I go to "/node/add/landing"
    And I fill in "Title" with "Aliased Landing page with camp"
    And I fill in "URL alias" with "/landing-page-with-camp-alias"
    Then I should see "Landing Header"
    And I press "Add Camp menu"
    And I press "Save and publish"
    Then I should see "Aliased Landing page with camp"
    And I should see " has been created."
    And I store the Node as "aliased-landing-node"

    # Create Camp page with camp menu with linked Landing by reference
    Given I go to "/node/add/camp"
    And I fill in "Title" with "Camp with camp menu and linked Landing by reference"
    And I fill in "URL" with stored Node "reference_fill" from "landing-node"
    And I fill in "Link text" with "Landing page with camp link"
    And I fill in the following:
      | Street address | 123 Test Road  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
    And I press "Add Camp menu"
    When I press "Save and publish"
    And I should see "Camp with camp menu and linked Landing by reference"
    And I should see " has been created."
    And I store the Node as "camp-node"
    And I should see " has been created."
    And I should see "Landing page with camp link"

    # Create Camp page with camp menu with linked Landing by alias
    Given I go to "/node/add/camp"
    And I fill in "Title" with "Camp with camp menu and linked Landing by alias"
    And I fill in "URL" with "/landing-page-with-camp-alias"
    And I fill in "Link text" with "Aliased Landing page with camp link"
    And I fill in the following:
      | Street address | 123 Test Road  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
    And I press "Add Camp menu"
    When I press "Save and publish"
    And I should see "Camp with camp menu and linked Landing by alias"
    And I should see " has been created."
    And I store the Node as "camp-node-alias"
    And I should see " has been created."
    And I should see "Aliased Landing page with camp link"

    # Check is menu links present on Camp and Landing page.
    Given I go to stored Node "system_url" from "landing-node"
    And I should see "Landing page with camp link"
    Given I go to stored Node "system_url" from "camp-node"
    And I should see "Landing page with camp link"

    Given I go to stored Node "system_url" from "aliased-landing-node"
    And I should see "Aliased Landing page with camp link"
    Given I go to stored Node "system_url" from "camp-node-alias"
    And I should see "Aliased Landing page with camp link"

    # Set homepage to Landing page with camp by alias
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with "/landing-page-with-camp-alias"
    Then I press the "Save configuration" button
    # Because "I should see the message" was not working on this page.
    And I go to "/admin/config/system/site-information"
    Then the "Default front page" field should contain "/landing-page-with-camp-alias"

    # Check is menu links present on Camp and front (Landing) page.
    Then I go to homepage
    And I should see "Aliased Landing page with camp link"
    Then I go to stored Node "alias_url" from "aliased-landing-node"
    And I should see "Aliased Landing page with camp link"

    # Set homepage to Landing other page with camp by system path.
    Given I go to "/admin/config/system/site-information"
    And I fill in "Default front page" with stored Node "system_url" from "landing-node"
    Then I press the "Save configuration" button
    # Because "I should see the message" was not working on this page.
    And I go to "/admin/config/system/site-information"
    #Then the "Default front page" field should contain stored Node "system_url" from "landing-node"

    # Check is menu links present on Camp and front (Landing) page.
    Then I go to homepage
    And I should see "Landing page with camp link"
    Then I go to stored Node "system_url" from "landing-node"
    And I should see "Landing page with camp link"
