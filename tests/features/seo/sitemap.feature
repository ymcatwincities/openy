@openy @api
Feature: Sitemap
  Ensure that sitemap is available and a landing page is present.

  Background: Add background content as needed
    Given I am logged in as a user with the Editor role
    Given I create taxonomy_term of type blog_category:
      | name               |
      | BEHAT CATEGORY ONE |
    And I create large branch content:
      | title                               | BEHAT BRANCH 01 |
      | field_location_address:country_code | US              |
      | :address_line1                      | Main road 10    |
      | :locality                           | Seattle         |
      | :administrative_area                | WA              |
      | :postal_code                        | 98101           |
      | field_location_coordinates:lat      | 47.293433       |
      | :lng                                | -122.238717     |
      | field_location_phone                | +1234567890     |

  Scenario: Create blog post and check XML sitemap
    When I go to "/node/add/blog"
    And I fill in "Title" with "Behat Sitemap Blog"
    And I select "BEHAT BRANCH 01" from "Location"
    And I press "Save"
    And I run cron
    And I am an anonymous user
    Then I go to "/sitemap.xml"
    Then I should see text "blog/behat-sitemap-blog" in XML

