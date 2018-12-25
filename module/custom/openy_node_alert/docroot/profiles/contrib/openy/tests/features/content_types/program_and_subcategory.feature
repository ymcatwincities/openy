@openy @api
Feature: Program and Subcategory pages
  As Admin I want to make sure that Program and Subcategory can be created.
  And I should see paragraph with Subcategory teaser on Program page.

  Background: Create necessary content for tests
    Given I am logged in as a user with the "Editor" role
    And I create taxonomy_term of type color:
      | KEY     | name          | field_color |
      | magenta | Behat Magenta | FF00FF      |
    Then I create media of type image:
      | KEY              | name             | field_media_image |
      | image_01         | Image 01         | image_01.png      |
    And I create large program content:
      | KEY                             | behat_program         |
      | title                           | BEHAT PROGRAM         |
      | field_program_color             | magenta               |
      | field_program_description:value | We rely on donations. |
      | :format                         | full_html             |
    And I create large paragraph of type banner:
      | KEY                    | behat_banner_01      | behat_banner_02      | behat_banner_03      |
      | field_prgf_headline    | BEHAT BANNER 01      | BEHAT BANNER 02      | BEHAT BANNER 03      |
      | field_prgf_image       | image_01             | image_01             | image_01             |
      | field_prgf_description | BEHAT DESCRIPTION 01 | BEHAT DESCRIPTION 02 | BEHAT DESCRIPTION 03 |
      | field_prgf_link:uri    | http://openymca.org  | http://openymca.org  | http://openymca.org  |
      | :title                 | Read about Open Y     | Read about Open Y     | Read about Open Y     |
    And I create large paragraph of type small_banner:
      | KEY                 | behat_small_banner |
      | field_prgf_headline | BEHAT SMALL BANNER |
      | field_prgf_image    | image_01           |
      | field_prgf_color    | magenta            |
    And I create large program_subcategory content:
      | KEY                    | behat_category     |
      | title                  | BEHAT CATEGORY     |
      | field_category_program | behat_program      |
      | field_header_content   | behat_banner_01    |
      | field_content          | behat_banner_02    |
      | field_bottom_content   | behat_banner_03    |
      | field_sidebar_content  | behat_small_banner |

  Scenario: Create basic program and subcategory and check fields
    Given I go to "/node/add/program"
    And I fill in "Title" with "Behat Fitness"
    And I select "Behat Magenta" from "Color"
    And I fill in the following:
      | Description | Program suggests fitness classes for all ages. |
    And I press "Add Categories Listing" in the "content_area"
    When I press "Save"
    Then I should see the message "Program Behat Fitness has been created."

    Given I am logged in as a user with the "Editor" role
    When I go to "/node/add/program_subcategory"
    And I fill in "Title" with "Behat Personal Training"
    And I fill in "Program" with "Behat Fitness"
    And I select "Behat Magenta" from "Color"
    And I fill media field "edit-field-category-image-target-id" with "media:1"
    And I fill in the following:
      | Description | Program suggests fitness classes for all ages. |
    When I press "Save"
    Then I should see the message "Program Subcategory Behat Personal Training has been created."

    When I go to "/programs/behat-fitness"
    Then I see the heading "Behat Fitness"
    And I should see the heading "Behat Personal Training"
    And I should see a ".subprogram-listing-item img" element
    And I should see "Program suggests fitness classes for all ages."
    And I should see the link "Open category"

  Scenario: I see appropriate content on program subcategory page
    Given I view node "behat_category"
    Then I should see "BEHAT CATEGORY"
    And I should see "BEHAT BANNER 01"
    And I should see "BEHAT DESCRIPTION 01"
    And I should see "BEHAT BANNER 02"
    And I should see "BEHAT DESCRIPTION 02"
    And I should see "BEHAT BANNER 03"
    And I should see "BEHAT DESCRIPTION 03"
    And I should see "BEHAT SMALL BANNER"
