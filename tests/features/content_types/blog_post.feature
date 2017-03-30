@openy @api
Feature: Blog Content type
  As Admin I want to make sure that Blog content type is OK

  Background: Add background content as needed
    Given I create "taxonomy_term" of type "blog_category":
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

  Scenario: Create basic blog and check fields
    Given I am logged in as a user with the Administrator role
    When I go to "/node/add/blog"
    And I fill in "Title" with "From OpenY Automation Blogger"
    And I select "BEHAT BRANCH 01" from "Location"
    And I fill in the following:
      | Category    | BEHAT CATEGORY ONE                          |
      | Description | This could be a draft for a wonderful post. |
    When I press "Save and publish"
    Then I should see the message "Blog Post From OpenY Automation Blogger has been created."
