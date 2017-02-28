@openy @api
Feature: Static Paragraphs
  Check that static paragraphs can be created and displayed

  Background: Create basic landing page
    Given that I log in with "admin" and "ffw"
    Given I am logged in as a user with the "Administrator" role
    And I go to "/node/add/landing"
    And I fill in "Title" with "Landing"

  Scenario: Create Small Banner
    When I press "Add Small Banner"
    And I fill in the following:
      |Headline | MY SMALL BANNER |
    And I fill media field "edit-field-prgf-image-target-id" with "media:1"
    And I enter "Red" for "Color"
    And I enter "Black" for "Color"
    And I press "Save and publish"
    Then I should see "MY SMALL BANNER"
    And I should see a ".banner-image img" element

  @houston_ignore
  Scenario: Create Banner
    When I press "Add Banner" in the "header_area"
    When I fill in the following:
      |Headline | MY BANNER |
      |Description | Enjoy the OpenY |
      |URL      | http://openymca.org     |
      |Link text | Read about OpenY |
    And I fill media field "edit-field-prgf-image-target-id" with "media:1"
    And I press "Save and publish"
    Then I should see the heading "MY BANNER"
    And I should see the text "Enjoy the OpenY"
    And I should see a ".banner-image img" element
    And I should see the link "Read about OpenY"

  Scenario: Create Gallery
    When I press "Add Gallery" in the "body" region
#    And I fill in the following:
#      | Headline | My Gallery |
#      | Description | The description is here. |
#      | URL         | http://openymca.org      |
#      | Link text   | Read about OpenY         |
    And I fill media field "edit-field-images-target-id" with "media:1"
    And I press "Save and publish"
#    Then I should see the heading "My Gallery"
#    And I should see "The description is here."
    And I should see a ".carousel img" element
#    And I should see the link "Read about OpenY"

  Scenario: Create Simple Content
    When I press "Add Simple Content" in the "body" region
    And I fill in "Body" with "Simple text is here."
    And I press "Save and publish"
    Then I should see "Simple text is here."

  @houston_ignore
  Scenario: Create Grid Content
    When I press "Add Grid Content"
    And I select "2 items per row" from "Style"
    And I press "Add Grid columns"
    And I fill in the following:
      | Headline | We Appreciate Your Support |
      | Icon Class | flag                     |
      | Description | Every year, we rely on donations. |
      | URL         | /donate                          |
      | Link text   | Donate                       |
    And I press "Save and publish"
    Then I should see the heading "We Appreciate Your Support"
    And I should see a "i.fa-flag" element
    And I should see "Every year, we rely on donations."
    And I should see the link "Donate"

  @houston_ignore
  Scenario: Create Promo Card
    When I press "Add Promo Card" in the "sidebar_area"
    And I fill in "Title" with "Promo" in the "sidebar_area"
    And I fill in the following:
      | Headline | OpenY is free to try! |
      | Description | Setup a website and see how it works. |
      | URL         | http://openymca.org   |
      | Link text   | Go!                   |
    And I press "Save and publish"
    Then I should see the heading "Promo"
    And I should see the heading "OpenY is free to try!"
    And I should see "Setup a website and see how it works."
    And I should see the link "Go!"

  @houston_ignore
  Scenario: Create Story Card
    When I press "Add Story Card" in the "sidebar_area"
    And I fill in "Title" with "New Story" in the "sidebar_area"
    And I fill in the following:
      | Headline | I discovered OpenY. And that looks great! |
      | URL      | http://openymca.org                       |
      | Link text| Discover OpenY                            |
    And I press "Save and publish"
    Then I should see the heading "New Story"
    And I should see "I discovered OpenY. And that looks great!"
    And I should see the link "Discover OpenY"

  @houston_ignore
  Scenario: Create Teaser
    When I press "Add Teaser" in the "content_area"
    And I fill in "Title" with "My Teaser" in the "content_area"
    And I fill in the following:
      | Description | Lorem ipsum dolor sit. |
      | URL         | /test                  |
      | Link text   | Test link              |
    And I fill media field "edit-field-prgf-image-target-id" with "media:1"
    And I press "Save and publish"
    Then I should see the heading "My Teaser"
    And I should see "Lorem ipsum dolor sit."
    And I should see a ".subprogram-listing-item img" element
    And I should see the link "Test link"
