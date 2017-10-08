@openy @api
Feature: Camp demo content
  I want to make sure that demo camps are present.

  Background: Login as admin
    Given I am logged in as a user with the "Administrator" role

  Scenario: See demo camps
    Given I go to "/admin/content?title=&type=camp&status=All&langcode=All"
    And I should see the link "Camp Colman"
    And I should see the link "Camp Orkila"
    And I should see the link "Camp Terry"

  Scenario: See demo membership Adult
    Given I go to "/admin/content?title=camp+colman&type=camp&status=All&langcode=All"
    And I click "Camp Colman"
    And I should see "WELCOME TO THE Camp Colman"
    And I should see "ADDRESS 20016 Bay Road KPS 98351 WA United States"
    And I should see "PHONE 9999999999"
    And I should see "Camp Colman"
    And I should see "Gallery for Camp Colman"
    And I should see an ".view-mode-prgf-gallery .field-media-image img" element
    And I should see the link "Home"
    And I should see the link "About"
    And I should see the link "Register"
    And I should see "Welcome to Camp Colman!"
    And I should see "Membership Has its Privileges"
    And I should see "What's New at Camp Colman?"
    And I should see "How does the camp experience benifit your child?"
    And I should see "Camp Colman Stories"
