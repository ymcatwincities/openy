@openy @javascript @api
Feature: WYSIWYG
  Check WYSIWYG

  Background: Create blog post to test WYSIWYG
    Given I am logged in as a user with the "Administrator" role
    And I go to "/node/add/blog"
    And I fill in "Title" with "Behat page"
    And I press "field_blog_description_simple_content_add_more"
    And I wait 20 seconds

  Scenario: Embed image
    When I click ".cke_button__embed_image" element
    And I wait 30 seconds
    And I switch to an iframe "entity_browser_iframe_images_library_embed"
    And I click "All Images"
    And I click ".image-style-browser-thumbnail" element
    And I press "Select images"
    And I switch back from an iframe
    And I wait 20 seconds
    And I select "Full" from "Display as"
    And I click "//button/span[.='Embed']" xpath element
    And I wait 20 seconds
    And I press "Save and publish"
    Then I should see an ".field-media-image img" element
    And I fill "title" with "slkd"
