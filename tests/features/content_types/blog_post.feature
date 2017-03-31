@openy @api
Feature: Blog Content type
  As Admin I want to make sure that Blog content type is OK

  Background: Add background content as needed
    Given I am logged in as a user with the Editor role
    Given I create taxonomy_term of type blog_category:
      | name               |
      | BEHAT CATEGORY ONE |
    And I create media of type image:
      | KEY              | name                 | field_media_image    |
      | behat_blog_image | behat_blog_image.jpg | behat_blog_image.jpg |
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
    When I go to "/node/add/blog"
    And I fill in "Title" with "Behat test blog"
    And I select "BEHAT BRANCH 01" from "Location"
    And I fill in the following:
      | Category    | BEHAT CATEGORY ONE                          |
      | Description | This could be a draft for a wonderful post. |
    When I press "Save and publish"
    Then I should see the message "Blog Post Behat test blog has been created."

  @javascript
  Scenario: Create blog post to add image
    When I go to "/node/add/blog"
    And I fill in "Title" with "Behat Image Blog"
    And I select "BEHAT BRANCH 01" from "Location"
    When I click "//*[@id='edit-group-content-area']/summary" xpath element
    When I click "//*[@id='edit-field-blog-image']/summary" xpath element
    And I press "Select images"
    And I wait for AJAX to finish
    Then I switch to an iframe "entity_browser_iframe_images_library"
    And I click "All Images"
    And I click "img.image-style-browser-thumbnail[title='behat_blog_image.jpg']" element
    And I press "Select images"
    Then I switch back from an iframe
    And I wait for AJAX to finish
    When I press "Save and publish"
    Then I should see the message "Blog Post Behat Image Blog has been created."
    And I should see an ".field-blog-image .field-media-image img" element
