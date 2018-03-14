@openy @api
Feature: Landing pages demo content
  I want to make sure that demo landing pages are present.

  Background: Login as admin
    Given I am logged in as a user with the "Administrator" role

  Scenario: See demo landing pages
    Given I go to "/admin/content?title=&type=landing_page&status=All&langcode=All"
    And I should see the link "Amenities at each Y"
    And I should see the link "Group Schedules (Embedded)"
    And I should see the link "Group Schedules"
    And I should see the link "Schedules"
    And I should see the link "Join"
    And I should see the link "Camp Terry page"
    And I should see the link "Camp Orkila page"
    And I should see the link "Camp Colman page"
    And I should see the link "Locations"
    And I should see the link "Accelerator"
    And I should see the link "Blog"
    And I should see the link "Give"
    And I should see the link "About the YMCA"
    And I should see the link "OpenY"

  Scenario: See demo landing page OpenY
    Given I go to "/admin/content?title=Openy&type=landing_page&status=All&langcode=All"
    And I click "OpenY"
    And I should see "OpenY Distribution"
    And I should see "EXPLORE THE OPEN Y!"
    And I should see "What's happening at the Y?"
    And I should see "Volunteers needed!"
    And I should see "We Appreciate Your Support"
    And I should see "Membership Has its Privileges"
