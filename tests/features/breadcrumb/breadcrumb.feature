@openy @api
Feature: Breadcrumbs on landing page
  As an Admin I want to make sure the breadcrumb links display properly on landing page.

  Background: Log in
    Given I am logged in as a user with the "Administrator" role
    # Create Landing page with breadcrumbs
    Given I go to "/node/add/landing_page"
    And I fill in "Title" with "Behat level 1"
    And I uncheck the box "Generate automatic URL alias"
    And I fill in "URL alias" with "/behat-level1"
    When I select "One Column" from "Layout"
    And I press "Save"
    Then I should see "Behat level 1"
   # Create Landing page with two level breadcrumbs
    And I go to "/node/add/landing_page"
    And I fill in "Title" with "Behat level 2"
    And I uncheck the box "Generate automatic URL alias"
    And I fill in "URL alias" with "/behat-level1/behat-level2"
    When I select "One Column" from "Layout"
    And I press "Save"
    Then I should see "Behat level 2"

  Scenario: Check breadcrumbs on page level 1
    When I go to "/behat-level1"
    Then I should see a ".breadcrumbs" element
    And I should see "Home" in the ".breadcrumbs" element
    And I should see "Behat level1" in the ".breadcrumbs" element

  Scenario: Check breadcrumbs on page level 2
    When I go to "/behat-level1/behat-level2"
    Then I should see a ".breadcrumbs" element
    And I should see "Home" in the ".breadcrumbs" element
    And I should see "Behat level1" in the ".breadcrumbs" element
    And I should see "Behat level2" in the ".breadcrumbs" element
