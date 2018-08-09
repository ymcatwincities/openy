@openy @api
Feature: Landing page Content type
  As Admin I want to make sure that Landing page content type is available with needed fields.

  Background: Create landing page content for review
    Given I am logged in as a user with the "Editor" role
    And I create media of type image:
      | KEY              | name             | field_media_image |
      | image_01         | Image 01         | image_01.png      |
    And I create large paragraph of type banner:
      | KEY                    | behat_banner_01      | behat_banner_02      | behat_banner_03      |
      | field_prgf_headline    | BEHAT BANNER 01      | BEHAT BANNER 02      | BEHAT BANNER 03      |
      | field_prgf_image       | image_01             | image_01             | image_01             |
      | field_prgf_description | BEHAT DESCRIPTION 01 | BEHAT DESCRIPTION 02 | BEHAT DESCRIPTION 03 |
      | field_prgf_link:uri    | http://openymca.org  | http://openymca.org  | http://openymca.org  |
      | :title                 | Read about OpenY     | Read about OpenY     | Read about OpenY     |
    And I create large landing_page content:
      | KEY                                 | behat_landing      |
      | title                               | BEHAT LANDING      |
      | field_header_content                | behat_banner_01    |
      | field_content                       | behat_banner_02    |
      | field_bottom_content                | behat_banner_03    |

  Scenario: Create basic landing page and check fields
    Given I go to "/node/add/landing_page"
    And I fill in "Title" with "Basic Landing"
    And I select "One Column" from "Layout"
    Then I should see "Header Area"
    And I should see "Content Area"
    And I should see "Sidebar Area"
    And I should see "Bottom Area"
    When I press "Save"
    Then I should see the message "Landing Page Basic Landing has been created."

  Scenario: I see appropriate content on landing page
    Given I view node "behat_landing"
    Then I should see "BEHAT LANDING"
    And I should see "BEHAT BANNER 01"
    And I should see "BEHAT DESCRIPTION 01"
    And I should see "BEHAT BANNER 02"
    And I should see "BEHAT DESCRIPTION 02"
    And I should see "BEHAT BANNER 03"
    And I should see "BEHAT DESCRIPTION 03"