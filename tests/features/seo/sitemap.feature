@openy @api
Feature: Sitemap
  Ensure that sitemap is available and a landing page is present.

  Scenario: Check sitemap
    Given I am an anonymous user
    When I go to "/sitemap.xml"
    Then I should see text "blog/mango-avocado-salsa" in XML
