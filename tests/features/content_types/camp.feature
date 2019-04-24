@openy @api
Feature: Camp Content type
  As Admin I want to make sure that Camp content type is available with needed fields.

  Background: Camp test setup
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
      | :title                 | Read about Open Y     | Read about Open Y     | Read about Open Y     |
    And I create large camp content:
      | KEY                                 | behat_camp         |
      | title                               | BEHAT CAMP         |
      | field_camp_menu_links:uri           | internal:/register |
      | :title                              | Registration       |
      | field_location_address:country_code | US                 |
      | :address_line1                      | Wood road 115      |
      | :locality                           | Seattle            |
      | :administrative_area                | WA                 |
      | :postal_code                        | 98101              |
      | field_location_coordinates:lat      | 47.293433          |
      | :lng                                | -122.238717        |
      | field_location_phone                | +1234567890        |
      | field_header_content                | behat_banner_01    |
      | field_content                       | behat_banner_02    |
      | field_bottom_content                | behat_banner_03    |

  Scenario: Create basic Camp
    Given I go to "/node/add/camp"
    And I fill in "Title" with "Camp One"
    And I fill in the following:
      | URL | /register |
      | Link text | Registration |
    And I fill in the following:
      | Street address | Wood road 115  |
      | City           | Seattle        |
      | State          | WA             |
      | Zip code       | 98101          |
      | Latitude       | 46.293433      |
      | Longitude      | -123.238717    |
      | Phone          | +1234567890    |
    When I press "Save"
    Then I should see the message "Camp Camp One has been created."

  Scenario: I see appropriate content on camp
    Given I view node "behat_camp"
    Then I should see "BEHAT CAMP"
    And I should see "Wood road 115"
    And I should see "98101"
    And I should see "WA"
    And I should see "Seattle"
    And I should see "+1234567890"
    And I should see "BEHAT BANNER 01"
    And I should see "BEHAT DESCRIPTION 01"
    And I should see "BEHAT BANNER 02"
    And I should see "BEHAT DESCRIPTION 02"
    And I should see "BEHAT BANNER 03"
    And I should see "BEHAT DESCRIPTION 03"
