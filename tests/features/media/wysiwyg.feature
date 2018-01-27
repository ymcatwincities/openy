@openy @javascript @api
Feature: WYSIWYG
  Check WYSIWYG

  Background: Create blog post to test WYSIWYG
    Given I am logged in as a user with the "Editor" role
    And I create paragraph of type simple_content:
      | KEY            |
      | simple_content |
    And I create media of type image:
      | name            | field_media_image |
      | behat_image.jpg | behat_image.jpg   |
    And I create media of type video:
      | name               | field_media_video_embed_field               | status |
      | BEHAT SAMPLE VIDEO | https://www.youtube.com/watch?v=C0DPdy98e4c | 1      |
    And I create media of type document:
      | name                  | field_media_document | field_media_mime | field_media_size |
      | BEHAT SAMPLE DOCUMENT | sample_document.pdf  | application/pdf  | 0                |
    And I view a landing_page content:
      | title           | Behat test for WYSIWYG |
      | field_lp_layout | one_column             |
      | field_content   | simple_content         |
    And I click "Edit"

  Scenario: Embed Image
    When I click "//*[@id='edit-group-content-area']/summary" xpath element
    And I click ".cke_button__embed_image" element
    And I wait for AJAX to finish
    Then I switch to an iframe "entity_browser_iframe_images_library_embed"
    And I click "All Images"
    And I click "img.image-style-browser-thumbnail[title='behat_image.jpg']" element
    And I press "Select images"
    Then I switch back from an iframe
    And I wait for AJAX to finish
    And I select "Full" from "Display as"
    And I click "//button[.='Embed']" xpath element
    Then I press "Save"
    And I should see an ".field-media-image img" element

  Scenario: Embed Video
    When I click "//*[@id='edit-group-content-area']/summary" xpath element
    And I click ".cke_button__embed_video_icon" element
    And I wait for AJAX to finish
    Then I switch to an iframe "entity_browser_iframe_videos_library_embed"
    And I click "Select Videos"
    And I click "img.image-style-thumbnail[title='BEHAT SAMPLE VIDEO']" element
    And I press "Select videos"
    Then I switch back from an iframe
    And I wait for AJAX to finish
    And I select "Full" from "Display as"
    And I click "//button[.='Embed']" xpath element
    Then I press "Save"
    And I should see an "div.media-video .video-embed-field-responsive-video iframe" element

  Scenario: Embed Document
    When I click "//*[@id='edit-group-content-area']/summary" xpath element
    And I click ".cke_button__embed_document_icon" element
    And I wait for AJAX to finish
    Then I switch to an iframe "entity_browser_iframe_documents_library_embed"
    And I click "Select Documents"
    And I click "img.image-style-thumbnail[title='BEHAT SAMPLE DOCUMENT']" element
    And I press "Select documents"
    Then I switch back from an iframe
    And I wait for AJAX to finish
    And I select "Full" from "Display as"
    And I click "//button[.='Embed']" xpath element
    Then I press "Save"
    And I should see an "div.media-document .field-media-document iframe" element