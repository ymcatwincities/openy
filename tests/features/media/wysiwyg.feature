@openy @javascript @api
Feature: WYSIWYG
  Check WYSIWYG

  Background: Create blog post to test WYSIWYG
    Given I am logged in as a user with the "Editor" role
    And I create "paragraph" of type "simple_content" with key for reference:
      | KEY     |
      | simple1 |
    And I view a "landing_page" content:
      | title           | Behat test for WYSIWYG |
      | field_lp_layout | one_column             |
      | field_content   | simple1                |
    And I click "Edit"
    And I wait 20 seconds

  Scenario: Embed image
    When I click "//*[@id='edit-group-content-area']/summary" xpath element
    And I wait 30 seconds
    And I click ".cke_button__embed_image" element
    And I wait for AJAX to finish
    And I switch to an iframe "entity_browser_iframe_images_library_embed"
    And I click "All Images"
    And I click ".image-style-browser-thumbnail" element
    And I press "Select images"
    And I switch back from an iframe
    And I wait for AJAX to finish
    And I select "Full" from "Display as"
    And I click "//button/span[.='Embed']" xpath element
    And I wait 20 seconds
    And I press "Save and keep published"
    Then I should see an ".field-media-image img" element