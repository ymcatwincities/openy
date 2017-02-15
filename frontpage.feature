Feature: Frontpage feature

  Background: Login BasicAuth
    Given that I log in with "admin" and "ffw"

  Scenario: Check frontpage is accessible
    Given that I log in with "admin" and "ffw"
    Given I am on homepage
