@openy @api
Feature: Facility demo content
  I want to make sure that demo camps are present.

  Background: Login as admin
    Given I am logged in as a user with the "Administrator" role

  Scenario: See demo facilities
    Given I go to "/admin/content?title=&type=facility&status=All&langcode=All"
    And I should see the link "Fauntleroy YMCA"
    And I should see the link "Fernwood Elementary"
    And I should see the link "Washington Elementary"
    And I should see the link "Magnuson Park"
    And I should see the link "Maple Elementary"

  Scenario: See demo membership Adult
    Given I go to "/admin/content?title=maple+elementary&type=facility&status=All&langcode=All"
    And I click "Maple Elementary"
    And I should see "Maple Elementary"
    And I should see "Social Responsibility"
    And I should see "Volunteers help the Y listen and respond to our region’s most critical social needs."
    And I should see "Before & After School Care"
    And I should see "4925 Corson Ave S"
    And I should see "Seattle, WA 98108"
    And I should see "United States"
    And I should see the link "9999999999"
    And I should see "Associated Branch"
    And I should see the link "West YMCA"
    And I should see "1111 Perimeter Rd SW 77001 TX United States"
    And I should see "Branch Hours Mon - Sun: closed"
