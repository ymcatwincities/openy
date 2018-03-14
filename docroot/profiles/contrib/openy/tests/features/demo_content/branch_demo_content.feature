@openy @api
Feature: Branch demo content
  I want to make sure that demo branch are present.

  Background: Login as admin
    Given I am logged in as a user with the "Administrator" role

  Scenario: See demo memberships
    Given I go to "/admin/content?title=&type=branch&status=All&langcode=All"
    And I should see the link "West YMCA"
    And I should see the link "Downtown YMCA"
    And I should see the link "East YMCA"
    And I should see the link "South YMCA"

  Scenario: See demo membership Adult
    Given I go to "/admin/content?title=west+ymca&type=branch&status=All&langcode=All"
    And I click "West YMCA"
    And I should see "WELCOME TO THE West YMCA"
    And I should see "ADDRESS 1111 Perimeter Rd SW 77001 TX United States"
    And I should see "PHONE 9999999999"
    And I should see "TODAY'S HOURS closed View all hours View all hours"
    And I should see "Save as preferred branch"
    And I should see "West YMCA"
    And I should see "Gallery for West YMCA"
    And I should see an ".view-mode-prgf-gallery .field-media-image img" element
    And I should see "Branch Amenities"
    And I should see "Become a Member"
    And I should see "Schedules"
    And I should see "What's New at West YMCA?"
    And I should see an "form.branch-specific-form" element
    And I should see an "#edit-groupex-pdf-link" element
    And I should see "Please note, you may search by class or instructor by clicking on the links in class cards."
    And I should see "Volunteers Needed"
    And I should see "We Appreciate Your Support"
    And I should see "Our Story"
