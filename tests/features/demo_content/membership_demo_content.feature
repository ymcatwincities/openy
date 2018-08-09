@openy @api
Feature: Membership demo content
  I want to make sure that demo membership are present.

  Background: Login as admin
    Given I am logged in as a user with the "Administrator" role

  Scenario: See demo memberships
    Given I go to "/admin/content?title=&type=membership&status=All&langcode=All"
    And I should see the link "Couple"
    And I should see the link "Adult"
    And I should see the link "Youth"
    And I should see the link "Teen/Young Adult"
    And I should see the link "Family 1"
    And I should see the link "Family 2"
    And I should see the link "Senior"

  Scenario: See demo membership Adult
    Given I go to "/admin/content?title=adult&type=membership&status=All&langcode=All"
    And I click "Adult"
    And I should see "Adults (30-64)"
    And I should see an ".field-media-image img" element
