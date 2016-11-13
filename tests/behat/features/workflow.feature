@fast-tests @headless @api
Feature: Editing workflow

  Scenario: Workflow for contributor
    Given I am logged in as a Contributor
    When I go to "node/add/article"
    And I fill in "Title" with "Contributed Article"
    And I fill in "Body" with "Initial content for review"
    And I select "Needs Review" from "Target state"
    And I press "Save"
    Then I should see "Contributed article"


  Scenario: Workflow - Check as anonymous
    When I go to "/contributed_article"
    Then I should get a 403 HTTP response

  Scenario: Workflow - Publish article
    Given I am logged in as an administrator
    And I am on "/contributed_article"
    Then I click "Edit"
    And I select "Published" from "Target State"
    And I press "Save"
    And I go to "/user/logout"
    And I go to "/contributed_article"
    Then I should get a 200 HTTP response
